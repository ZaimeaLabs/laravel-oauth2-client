<?php

namespace Zaimea\OAuth2Client\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Zaimea\OAuth2Client\Core\Manager;
use Zaimea\OAuth2Client\Models\OauthProvider;

class ConnectController extends Controller
{
    protected Manager $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * List providers and attached records for current user
     */
    public function index(Request $request)
    {
        $providers = array_keys(config('oauth2-client.providers', []));
        $attached = [];
        if (Auth::check()) {
            $attached = OauthProvider::where('user_id', Auth::id())->get()->keyBy('provider')->toArray();
        }
        return view('oauth2-client::index', compact('providers','attached'));
    }

    public function redirectToProvider(Request $request, string $provider)
    {
        $drv = $this->manager->driver($provider);
        $auth = $drv->redirectUrl();

        // persist PKCE verifier and state
        if (!empty($auth['code_verifier'])) {
            Session::put("oauth.{$provider}.code_verifier", $auth['code_verifier']);
        }
        if (!empty($auth['state'])) {
            Session::put("oauth.{$provider}.state", $auth['state']);
        }

        Log::info('OAuth redirect', ['provider' => $provider, 'has_code_verifier' => !empty($auth['code_verifier']), 'state' => $auth['state'] ?? null]);
        return redirect()->away($auth['url']);
    }

    public function callback(Request $request, string $provider)
    {
        $user = Auth::user();
        if (!$user) return redirect()->route('login');

        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('oauth2-client.providers.index')->with('error','Authorization failed');
        }

        $drv = $this->manager->driver($provider);

        // state
        $storedState = Session::pull("oauth.{$provider}.state", null);
        if ($storedState !== null && $storedState !== $request->get('state')) {
            Log::warning('OAuth state mismatch', ['provider'=>$provider,'req'=>$request->get('state'),'stored'=>$storedState]);
            return redirect()->route('oauth2-client.providers.index')->with('error','Invalid state');
        }

        $codeVerifier = Session::pull("oauth.{$provider}.code_verifier", null);
        $options = [];
        if ($codeVerifier) $options['code_verifier'] = $codeVerifier;

        try {
            $tokenData = $drv->getAccessToken($code, $options);
        } catch (\Throwable $e) {
            report($e);
            Log::error('OAuth token exchange failed', ['provider'=>$provider,'error'=>$e->getMessage()]);
            return redirect()->route('oauth2-client.providers.index')->with('error','Token exchange failed');
        }

        Log::info('OAuth token exchange result', [
            'provider' => $provider,
            'has_access_token' => !empty($tokenData['access_token'] ?? null),
            'raw_keys' => isset($tokenData['raw']) ? array_keys($tokenData['raw']) : null,
        ]);

        $accessToken = $tokenData['access_token'] ?? null;
        if (empty($accessToken)) {
            Log::error('No access_token obtained from provider', ['provider'=>$provider,'tokenData'=>$tokenData]);
            return redirect()->route('oauth2-client.providers.index')->with('error','No access token returned by provider');
        }

        $remoteUser = $drv->userFromToken($accessToken);
        // encrypt tokens if configured
        $encrypt = config('oauth2-client.encrypt_tokens', true);
        $storedAccess = $encrypt ? Crypt::encryptString($accessToken) : $accessToken;
        $storedRefresh = isset($tokenData['refresh_token']) ? ($encrypt ? Crypt::encryptString($tokenData['refresh_token']) : $tokenData['refresh_token']) : null;

        OauthProvider::updateOrCreate(
            ['user_id' => $user->id, 'provider' => $provider],
            [
                'provider_user_id' => $remoteUser['id'] ?? null,
                'access_token' => $storedAccess,
                'refresh_token' => $storedRefresh,
                'expires_at' => isset($tokenData['expires_in']) ? now()->addSeconds($tokenData['expires_in']) : null,
                'scopes' => $tokenData['raw']['scope'] ?? null,
                'meta' => $remoteUser,
            ]
        );

        return redirect()->route('oauth2-client.providers.index')->with('success','Provider connected: '.$provider);
    }

    public function detach(Request $request, string $provider)
    {
        $user = Auth::user();
        if (!$user) return redirect()->route('login');

        $record = OauthProvider::where('user_id', $user->id)->where('provider', $provider)->first();
        if (!$record) return back()->with('error','Not attached');

        try {
            // revoke token via provider
            $drv = $this->manager->driver($provider);

            // decrypt token if necessary
            $decrypt = config('oauth2-client.encrypt_tokens', true);
            $access = $decrypt ? \Illuminate\Support\Facades\Crypt::decryptString($record->access_token) : $record->access_token;

            $drv->revokeToken($access);
        } catch (\Throwable $e) {
            report($e);
        }

        $record->delete();
        return back()->with('success','Detached');
    }
}
