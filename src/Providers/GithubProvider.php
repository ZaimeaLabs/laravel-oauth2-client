<?php

namespace Zaimea\OAuth2Client\Providers;

use Illuminate\Support\Facades\Http;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

class GithubProvider extends ProviderAbstract
{
    protected function makeProvider(): Github
    {
        return new Github([
            'clientId' => $this->config['client_id'],
            'clientSecret' => $this->config['client_secret'],
            'redirectUri' => $this->config['redirect'],
        ]);
    }

    public function userFromToken(string $accessToken): array
    {
        $tokenObj = new AccessToken(['access_token' => $accessToken]);
        $owner = $this->oauthProvider->getResourceOwner($tokenObj);
        $data = method_exists($owner,'toArray') ? $owner->toArray() : (array)$owner;
        // Normalize common fields
        return [
            'id' => $data['id'] ?? $data['node_id'] ?? null,
            'login' => $data['login'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'raw' => $data,
        ];
    }

    public function revokeToken(?string $accessToken = null): bool
    {
        // GitHub App revocation requires Basic auth with client_id:client_secret and hits
        // https://api.github.com/applications/:client_id/token => DELETE, per docs.
        $token = $accessToken;
        if (!$token) return false;

        $clientId = $this->config['client_id'];
        $clientSecret = $this->config['client_secret'];

        $url = "https://api.github.com/applications/{$clientId}/token";
        $resp = Http::withBasicAuth($clientId, $clientSecret)
            ->delete($url, ['access_token' => $token]);

        return $resp->successful();
    }
}
