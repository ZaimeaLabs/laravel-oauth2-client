<?php

namespace Zaimea\OAuth2Client\Providers;

use League\OAuth2\Client\Provider\GenericProvider;
use Illuminate\Support\Facades\Http;

class InstagramProvider extends ProviderAbstract
{
    protected function makeProvider(): GenericProvider
    {
        return new GenericProvider([
            'clientId' => $this->config['client_id'],
            'clientSecret' => $this->config['client_secret'],
            'redirectUri' => $this->config['redirect'],
            'urlAuthorize' => $this->config['authorize_url'] ?? 'https://api.instagram.com/oauth/authorize',
            'urlAccessToken' => $this->config['token_url'] ?? 'https://api.instagram.com/oauth/access_token',
            'urlResourceOwnerDetails' => $this->config['resource_url'] ?? 'https://graph.instagram.com/me',
        ]);
    }

    public function userFromToken(string $accessToken): array
    {
        $resp = Http::withToken($accessToken)->get($this->config['resource_url'] ?? 'https://graph.instagram.com/me', [
            'fields' => 'id,username,account_type'
        ]);
        if ($resp->successful()) {
            $data = $resp->json();
            return [
                'id' => $data['id'] ?? null,
                'username' => $data['username'] ?? null,
                'raw' => $data,
            ];
        }
        return [];
    }

    public function revokeToken(?string $accessToken = null): bool
    {
        // Instagram long-lived tokens can be revoked by deleting permissions or via Graph API if available.
        return false;
    }
}
