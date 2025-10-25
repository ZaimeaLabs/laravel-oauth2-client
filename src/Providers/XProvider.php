<?php

namespace Zaimea\OAuth2Client\Providers;

use League\OAuth2\Client\Provider\GenericProvider;
use Illuminate\Support\Facades\Http;

class XProvider extends ProviderAbstract
{
    protected function makeProvider(): GenericProvider
    {
        return new GenericProvider([
            'clientId' => $this->config['client_id'],
            'clientSecret' => $this->config['client_secret'],
            'redirectUri' => $this->config['redirect'],
            'urlAuthorize' => $this->config['authorize_url'] ?? 'https://twitter.com/i/oauth2/authorize',
            'urlAccessToken' => $this->config['token_url'] ?? 'https://api.twitter.com/2/oauth2/token',
            'urlResourceOwnerDetails' => $this->config['resource_url'] ?? 'https://api.twitter.com/2/users/me',
        ]);
    }

    public function userFromToken(string $accessToken): array
    {
        $resp = Http::withToken($accessToken)->get($this->config['resource_url'] ?? 'https://api.twitter.com/2/users/me');
        if ($resp->successful()) {
            $data = $resp->json();
            return [
                'id' => $data['data']['id'] ?? null,
                'username' => $data['data']['username'] ?? null,
                'raw' => $data,
            ];
        }
        return [];
    }

    public function revokeToken(?string $accessToken = null): bool
    {
        // X (Twitter) provides a revoke endpoint for OAuth2 tokens for apps with appropriate permissions
        $token = $accessToken;
        if (!$token) return false;
        $resp = Http::withBasicAuth($this->config['client_id'], $this->config['client_secret'])
            ->asForm()->post($this->config['revoke_url'] ?? 'https://api.twitter.com/2/oauth2/revoke', [
                'token' => $token,
            ]);
        return $resp->successful();
    }
}
