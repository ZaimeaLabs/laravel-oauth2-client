<?php

namespace Zaimea\OAuth2Client\Providers;

use League\OAuth2\Client\Provider\Github;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function getAccessToken(string $code, array $options = []): array
    {
        $res = parent::getAccessToken($code, $options);

        if (empty($res['access_token'])) {
            Log::warning('League client returned no access token, attempting manual GitHub exchange', ['raw'=>$res['raw'] ?? null]);

            $clientId = $this->config['client_id'] ?? env('GITHUB_CLIENT_ID');
            $clientSecret = $this->config['client_secret'] ?? env('GITHUB_CLIENT_SECRET');
            $redirect = $this->config['redirect'] ?? env('GITHUB_REDIRECT');

            try {
                $resp = Http::asForm()->withHeaders(['Accept'=>'application/json'])->post('https://github.com/login/oauth/access_token', [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'code' => $code,
                    'redirect_uri' => $redirect,
                ]);
                if ($resp->ok()) {
                    $body = $resp->json();
                    return $this->formatAccessToken($body);
                }
                Log::error('Manual GitHub exchange failed', ['status'=>$resp->status(),'body'=>$resp->body()]);
            } catch (\Throwable $e) {
                Log::error('Manual GitHub exchange exception', ['error'=>$e->getMessage()]);
            }
        }

        return $res;
    }

    public function revokeToken(?string $accessToken = null): bool
    {
        if (!$accessToken) return false;
        $clientId = $this->config['client_id'] ?? null;
        $clientSecret = $this->config['client_secret'] ?? null;
        if (!$clientId || !$clientSecret) return false;

        $url = "https://api.github.com/applications/{$clientId}/token";
        $resp = Http::withBasicAuth($clientId, $clientSecret)->delete($url, ['access_token'=>$accessToken]);
        return $resp->successful();
    }
}
