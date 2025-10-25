<?php

namespace Zaimea\OAuth2Client\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Zaimea\OAuth2Client\Manager;
use Zaimea\OAuth2Client\Models\OauthProvider;

class ConnectController extends Controller
{
    protected Manager $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function index(Request $request)
    {
        // list available providers from config and attached providers for user
        $providers = array_keys(config('oauth2-client.providers', []));
        $attached = [];
        if (Auth::check()) {
            $attached = OauthProvider::where('user_id', Auth::id())->get()->keyBy('provider')->toArray();
        }

        return view('oauth2-client::providers.index', compact('providers','attached'));
    }

    public function show(Request $request, $provider)
    {
                $attached = [];
                if (Auth::check()) {
                    $attached = OauthProvider::where('user_id', Auth::id())->where('provider', $provider)->first();
                }

                return view('oauth2-client::providers.show', compact('provider','attached'));
            }

            public function redirectToProvider(Request $request, $provider)
    {
        $drv = $this->manager->driver($provider);
        $auth = $drv->redirectUrl();
        if (is_array($auth)) {
            Session::put("oauth.{$provider}.code_verifier", $auth['code_verifier']);
            return redirect()->away($auth['url']);
        }
        return redirect()->away($auth);
    }

    public function callback(Request $request, $provider)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }
        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('oauth2-client.providers.index')->with('error','Authorization failed');
        }
        $drv = $this->manager->driver($provider);
        $codeVerifier = Session::pull("oauth.{$provider}.code_verifier", null);
        $options = [];
        if ($codeVerifier) $options['code_verifier'] = $codeVerifier;
        try {
            $tokenData = $drv->getAccessToken($code, $options);
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('oauth2-client.providers.index')->with('error','Token exchange failed');
        }
        $remoteUser = $drv->userFromToken($tokenData['access_token'] ?? '');
        OauthProvider::updateOrCreate(
            ['user_id' => $user->id, 'provider' => $provider],
            [
                'provider_user_id' => $remoteUser['id'] ?? null,
                'access_token' => $tokenData['access_token'] ?? null,
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => isset($tokenData['expires_in']) ? now()->addSeconds($tokenData['expires_in']) : null,
                'scopes' => $tokenData['raw']['scope']
                                ?? ($drv->getScopes() ? $drv->getScopes() : null),
                'meta' => $remoteUser,
            ]
        );

        return redirect()->route('oauth2-client.providers.index')->with('success','Provider connected: '.$provider);
    }

    public function detach(Request $request, $provider)
    {
        $user = Auth::user();
        if (!$user) return redirect()->route('login');
        $record = OauthProvider::where('user_id',$user->id)->where('provider',$provider)->first();
        if (!$record) return back()->with('error','Not attached');
        try {
            $drv = $this->manager->driver($provider);
            $drv->revokeToken($record->access_token);
        } catch (\Throwable $e) {
            report($e);
        }
        $record->delete();
        return back()->with('success','Detached');
    }
}
