<?php

namespace Zaimea\OAuth2Client\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $auth = $drv->redirectUrl(); // acum întotdeauna array: ['url','code_verifier','state']

        // Debug: log the redirect data (remove in prod)
        Log::info('OAuth redirect', [
            'provider' => $provider,
            'has_code_verifier' => !empty($auth['code_verifier']),
            'state' => $auth['state'] ?? null,
        ]);

        // store PKCE verifier and state in session
        if (!empty($auth['code_verifier'])) {
            Session::put("oauth.{$provider}.code_verifier", $auth['code_verifier']);
        }
        if (!empty($auth['state'])) {
            Session::put("oauth.{$provider}.state", $auth['state']);
        }

        return redirect()->away($auth['url']);
    }

    public function callback(Request $request, $provider)
    {
        $user = Auth::user();
        if (!$user) return redirect()->route('login');

        $code = $request->get('code');
        $reqState = $request->get('state');

        // Debug: log incoming request values
        Log::info('OAuth callback hit', [
            'provider' => $provider,
            'code' => $code ? 'present' : 'missing',
            'state' => $reqState,
            'session_state' => Session::get("oauth.{$provider}.state"),
            'session_code_verifier_exists' => Session::has("oauth.{$provider}.code_verifier"),
        ]);

        if (!$code) {
            return redirect()->route('oauth2-client.providers.index')->with('error', 'Authorization failed (no code).');
        }

        // Validate state if we stored it previously
        $storedState = Session::pull("oauth.{$provider}.state", null);
        if ($storedState !== null) {
            if ($reqState !== $storedState) {
                Log::warning('OAuth state mismatch', [
                    'provider' => $provider,
                    'req_state' => $reqState,
                    'stored_state' => $storedState,
                ]);
                return redirect()->route('oauth2-client.providers.index')->with('error', 'Invalid OAuth state (possible CSRF).');
            }
        } else {
            // no stored state — log and continue (optionally treat as error)
            Log::warning('No OAuth state found in session (session maybe lost)', ['provider' => $provider]);
        }

        $drv = $this->manager->driver($provider);

        // PKCE verifier (dacă folosit) — pull so it's removed from session
        $codeVerifier = Session::pull("oauth.{$provider}.code_verifier", null);
        $options = [];
        if ($codeVerifier) {
            $options['code_verifier'] = $codeVerifier;
        }

        try {
            $tokenData = $drv->getAccessToken($code, $options);
        } catch (\Throwable $e) {
            report($e);
            Log::error('OAuth token exchange failed', ['provider' => $provider, 'error' => $e->getMessage()]);
            return redirect()->route('oauth2-client.providers.index')->with('error', 'Token exchange failed');
        }

        // Debug: inspect what we got
        Log::info('OAuth exchange full tokenData', [
            'provider' => $provider,
            'tokenData' => is_object($tokenData) && method_exists($tokenData, 'getValues')
                ? $tokenData->getValues()
                : $tokenData,
        ]);

        // Normalize: accept either array or League AccessToken object
        if ($tokenData instanceof \League\OAuth2\Client\Token\AccessToken) {
            $vals = $tokenData->getValues();
            $tokenData = [
                'access_token'  => $vals['access_token'] ?? null,
                'refresh_token' => $vals['refresh_token'] ?? null,
                'expires_in'    => $vals['expires_in'] ?? null,
                'raw'           => $vals,
            ];
        } elseif (!is_array($tokenData)) {
            // if weird type, cast to array safe
            $tokenData = (array) $tokenData;
        }

        // now check for access_token
        $accessToken = $tokenData['access_token'] ?? ($tokenData['token'] ?? null);
        if (empty($accessToken)) {
            Log::error('No access_token obtained from provider', ['provider' => $provider, 'tokenData' => $tokenData]);
            return redirect()->route('oauth2-client.providers.index')->with('error', 'No access token returned by provider');
        }

        // get remote user
        $remoteUser = $drv->userFromToken($accessToken);

        // save provider
        OauthProvider::updateOrCreate(
            ['user_id' => $user->id, 'provider' => $provider],
            [
                'provider_user_id' => $remoteUser['id'] ?? null,
                'access_token' => $accessToken,
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => isset($tokenData['expires_in']) ? now()->addSeconds($tokenData['expires_in']) : null,
                'scopes' => $tokenData['raw']['scope'] ?? $drv->getScopes() ?? null,
                'meta' => $remoteUser,
            ]
        );

        return redirect()->route('oauth2-client.providers.index')->with('success', 'Provider connected: ' . $provider);
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
