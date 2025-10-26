<?php

namespace Zaimea\OAuth2Client\Providers;

use League\OAuth2\Client\Token\AccessToken;
use Illuminate\Support\Facades\Log;

abstract class ProviderAbstract
{
    protected array $config;
    protected \League\OAuth2\Client\Provider\AbstractProvider $oauthProvider;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->oauthProvider = $this->makeProvider();
    }

    abstract protected function makeProvider(): \League\OAuth2\Client\Provider\AbstractProvider;

    public function redirectUrl(array $options = []): array
    {
        if (empty($options) && !empty($this->config['scopes'])) {
            $options['scope'] = $this->config['scopes'];
        }

        $verifier = null;
        if (!empty($this->config['use_pkce'])) {
            $verifier = $this->generateCodeVerifier();
            $challenge = $this->codeChallenge($verifier);
            $options['code_challenge'] = $challenge;
            $options['code_challenge_method'] = 'S256';
        }

        $url = $this->oauthProvider->getAuthorizationUrl($options);
        $state = method_exists($this->oauthProvider, 'getState') ? $this->oauthProvider->getState() : null;

        return ['url'=>$url,'code_verifier'=>$verifier,'state'=>$state];
    }

    protected function generateCodeVerifier(int $length = 64): string
    {
        $bytes = random_bytes($length);
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    protected function codeChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    public function getAccessToken(string $code, array $options = []): array
    {
        $params = array_merge(['code' => $code], $options);

        if (!isset($params['redirect_uri']) && !empty($this->config['redirect'])) {
            $params['redirect_uri'] = $this->config['redirect'];
        }

        try {
            $token = $this->oauthProvider->getAccessToken('authorization_code', $params);
            if ($token instanceof AccessToken) {
                return $this->formatAccessToken($token->getValues());
            }
            return $this->formatAccessToken((array) $token);
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            Log::warning('IdentityProviderException during token exchange', [
                'provider' => static::class,
                'message' => $e->getMessage(),
                'response' => method_exists($e, 'getResponseBody') ? $e->getResponseBody() : null,
            ]);
            $resp = method_exists($e, 'getResponseBody') ? $e->getResponseBody() : null;
            return $this->formatAccessToken(is_array($resp) ? $resp : []);
        } catch (\Throwable $e) {
            Log::error('Unexpected error getting access token', [
                'provider' => static::class,
                'error' => $e->getMessage(),
            ]);
            return ['access_token' => null, 'refresh_token' => null, 'expires_in' => null, 'raw' => []];
        }
    }

    protected function formatAccessToken(array $values): array
    {
        return [
            'access_token' => $values['access_token'] ?? $values['token'] ?? null,
            'refresh_token' => $values['refresh_token'] ?? ($values['refreshToken'] ?? null),
            'expires_in' => $values['expires_in'] ?? null,
            'raw' => $values,
        ];
    }

    public function userFromToken(AccessToken|string $accessToken): array
    {
        try {
            $tokenObj = $accessToken instanceof AccessToken ? $accessToken : new AccessToken(['access_token'=>$accessToken]);

            $owner = $this->oauthProvider->getResourceOwner($tokenObj);
            $data = method_exists($owner,'toArray') ? $owner->toArray() : (array)$owner;

            return $this->normalizeResourceOwner($data);
        } catch (\Throwable $e) {
            Log::error('userFromToken failed', ['provider'=>static::class,'error'=>$e->getMessage()]);
            return [];
        }
    }

    protected function normalizeResourceOwner(array $data): array
    {
        return $data;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function revokeToken(?string $accessToken = null): bool
    {
        return false;
    }
}
