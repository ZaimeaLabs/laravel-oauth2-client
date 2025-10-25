<?php

namespace Zaimea\OAuth2Client\Providers;

use Illuminate\Support\Facades\Http;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Token\AccessToken;

class GoogleProvider extends ProviderAbstract
{
    protected function makeProvider(): Google
    {
        return new Google([
            'clientId' => $this->config['client_id'],
            'clientSecret' => $this->config['client_secret'],
            'redirectUri' => $this->config['redirect'],
            'accessType' => $this->config['access_type'] ?? 'offline',
            'hostedDomain' => $this->config['hosted_domain'] ?? null,
        ]);
    }

    public function userFromToken(string $accessToken): array
    {
        $tokenObj = new AccessToken(['access_token' => $accessToken]);
        $owner = $this->oauthProvider->getResourceOwner($tokenObj);
        $data = method_exists($owner,'toArray') ? $owner->toArray() : (array)$owner;
        return [
            'id' => $data['sub'] ?? $data['id'] ?? null,
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? null,
            'raw' => $data,
        ];
    }

    public function revokeToken(?string $accessToken = null): bool
    {
        $token = $accessToken;
        if (!$token) return false;
        $resp = Http::asForm()->post('https://oauth2.googleapis.com/revoke', ['token' => $token]);
        return $resp->successful();
    }
}
