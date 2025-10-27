<?php
namespace Zaimea\OAuth2Client\Providers;

use League\OAuth2\Client\Provider\Github;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GitHub provider wrapper.
 *
 * Notes:
 *  - Uses league/oauth2-github provider by default.
 *  - Implements manual fallback token exchange if league returns no token.
 *  - Implements revokeToken via GitHub OAuth App token deletion endpoint (requires client id/secret).
 */
class GithubProvider extends ProviderAbstract
{
    protected function makeProvider(): \League\OAuth2\Client\Provider\AbstractProvider
    {
        return new Github([
            'clientId' => $this->config['client_id'] ?? null,
            'clientSecret' => $this->config['client_secret'] ?? null,
            'redirectUri' => $this->config['redirect'] ?? null,
            'scope' => $this->config['scopes'] ?? null,
        ]);
    }

    /**
     * Override getAccessToken to add manual exchange fallback.
     *
     * @param string $code
     * @param array $options
     * @return array normalized token array
     */
    public function getAccessToken(string $code, array $options = []): array
    {
        // First try league (preferred)
        $res = parent::getAccessToken($code, $options);

        // If no access_token (league failure), attempt manual exchange
        if (empty($res['access_token'])) {
            Log::warning('League client returned no access token — trying manual GitHub exchange', ['raw' => $res['raw'] ?? null]);

            $clientId = $this->config['client_id'] ?? env('GITHUB_CLIENT_ID');
            $clientSecret = $this->config['client_secret'] ?? env('GITHUB_CLIENT_SECRET');
            $redirect = $this->config['redirect'] ?? env('GITHUB_REDIRECT');

            $payload = [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'redirect_uri' => $redirect,
            ];

            if (!empty($options['code_verifier'])) {
                $payload['code_verifier'] = $options['code_verifier'];
            }

            $resp = Http::asForm()->withHeaders(['Accept' => 'application/json'])
                        ->post('https://github.com/login/oauth/access_token', $payload);

            Log::info('Manual GitHub exchange response', [
                'status' => $resp->status(),
                'body' => $resp->body(),
                'json' => $resp->json() ?? null
            ]);

            if ($resp->ok()) {
                return $this->formatAccessToken($resp->json());
            }

            Log::error('Manual GitHub exchange failed', ['status' => $resp->status(), 'body' => $resp->body()]);
        }

        return $res;
    }

    /**
     * Revoke token (GitHub OAuth App token delete)
     * Docs: https://docs.github.com/rest/apps/oauth-applications#delete-an-app-token
     *
     * @param string|null $accessToken
     * @return bool
     */
    public function revokeToken(?string $accessToken = null): bool
    {
        if (!$accessToken) return false;
        $clientId = $this->config['client_id'] ?? env('GITHUB_CLIENT_ID');
        $clientSecret = $this->config['client_secret'] ?? env('GITHUB_CLIENT_SECRET');
        if (!$clientId || !$clientSecret) return false;

        $url = "https://api.github.com/applications/{$clientId}/token";
        $resp = Http::withBasicAuth($clientId, $clientSecret)->delete($url, ['access_token' => $accessToken]);

        return $resp->successful();
    }
}
