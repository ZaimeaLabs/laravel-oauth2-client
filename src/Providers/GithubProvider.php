<?php

namespace Zaimea\OAuth2Client\Providers;

use Illuminate\Support\Facades\Http;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use League\OAuth2\Client\Provider\GenericProvider;

class GithubProvider extends ProviderAbstract
{
    protected function makeProvider(): GenericProvider
    {
        return new GenericProvider([
            'clientId'                => $this->config['client_id'],
            'clientSecret'            => $this->config['client_secret'],
            'redirectUri'             => $this->config['redirect'],
            'urlAuthorize'            => 'https://github.com/login/oauth/authorize',
            'urlAccessToken'          => 'https://github.com/login/oauth/access_token',
            'urlResourceOwnerDetails' => 'https://api.github.com/user',
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'Zaimea-OAuth2-Client'
            ],
        ]);
    }

    public function userFromToken(string|AccessToken $accessToken): array
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
        // optional: revoke with GitHub App endpoint if configured
        return parent::revokeToken($accessToken);
    }
}
