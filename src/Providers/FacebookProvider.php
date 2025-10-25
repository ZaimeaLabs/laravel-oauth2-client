<?php

namespace Zaimea\OAuth2Client\Providers;

use League\OAuth2\Client\Provider\Facebook;
use Illuminate\Support\Facades\Http;

class FacebookProvider extends ProviderAbstract
{
    protected function makeProvider(): Facebook
    {
        return new Facebook([
            'clientId' => $this->config['client_id'],
            'clientSecret' => $this->config['client_secret'],
            'redirectUri' => $this->config['redirect'],
            'graphApiVersion' => $this->config['graph_api_version'] ?? 'v16.0',
        ]);
    }

    public function userFromToken(string $accessToken): array
    {
        $token = $this->oauthProvider->getAccessToken('bearer', ['access_token' => $accessToken]);
        $owner = $this->oauthProvider->getResourceOwner($token);
        $data = method_exists($owner,'toArray') ? $owner->toArray() : (array)$owner;
        return [
            'id' => $data['id'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'raw' => $data,
        ];
    }

    public function revokeToken(?string $accessToken = null): bool
    {
        // For Facebook, invalidate the token via Graph API /debug_token with app access
        $token = $accessToken;
        if (!$token) return false;

        $appToken = $this->config['client_id'] . '|' . $this->config['client_secret'];
        $resp = Http::get('https://graph.facebook.com/debug_token', [
            'input_token' => $token,
            'access_token' => $appToken,
        ]);

        return $resp->successful();
    }
}
