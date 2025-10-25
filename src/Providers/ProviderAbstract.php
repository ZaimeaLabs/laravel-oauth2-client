<?php

namespace Zaimea\OAuth2Client\Providers;

use Zaimea\OAuth2Client\Contracts\ProviderInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;

abstract class ProviderAbstract implements ProviderInterface
{
    protected array $config;
    protected AbstractProvider $oauthProvider;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->oauthProvider = $this->makeProvider();
    }

    /**
     * Return full provider config array.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Return configured scopes or null.
     *
     * @return array|null
     */
    public function getScopes(): ?array
    {
        return $this->config['scopes'] ?? null;
    }

    abstract protected function makeProvider(): AbstractProvider;

    /**
     * Generate authorization redirect data.
     *
     * @param  array  $options
     * @return array{url: string, code_verifier: ?string}
     */
    public function redirectUrl(array $options = []): array
    {
        // Add scopes from config if not provided
        if (empty($options) && !empty($this->config['scopes'])) {
            $options['scope'] = $this->config['scopes'];
        }

        // PKCE support: if configured, generate code_challenge and add to options
        if (!empty($this->config['use_pkce'])) {
            $verifier = $this->generateCodeVerifier();
            $challenge = $this->codeChallenge($verifier);
            // Consumers should persist the code_verifier (e.g. in session) and the state if needed
            $options['code_challenge'] = $challenge;
            $options['code_challenge_method'] = 'S256';
            $options['__pkce_code_verifier'] = $verifier; // helper return only, not sent to provider
        }

        $url = $this->oauthProvider->getAuthorizationUrl($options);
        // If PKCE was used we provide the verifier so callers can persist it (e.g. session)
        if (!empty($options['__pkce_code_verifier'])) {
            $verifier = $options['__pkce_code_verifier'];
            unset($options['__pkce_code_verifier']);
            return ['url' => $url, 'code_verifier' => $verifier];
        }

        return [
            'url' => $url,
            'code_verifier' => $options['__pkce_code_verifier'] ?? null,
        ];
    }

    /**
     * Generate a high-entropy code_verifier for PKCE
     */
    protected function generateCodeVerifier(int $length = 64): string
    {
        // length between 43 and 128
        $bytes = random_bytes($length);
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /**
     * Create S256 code_challenge from verifier
     */
    protected function codeChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    public function getAccessToken(string $code, array $options = []): array
    {
        $params = array_merge(['code' => $code], $options);
        $accessToken = $this->oauthProvider->getAccessToken('authorization_code', $params);
        return $this->formatAccessToken($accessToken->getValues());
    }

    public function refreshAccessToken(string $refreshToken, array $options = []): array
    {
        $params = array_merge(['refresh_token' => $refreshToken], $options);
        $token = $this->oauthProvider->getAccessToken('refresh_token', $params);
        return $this->formatAccessToken($token->getValues());
    }

    protected function formatAccessToken(array $values): array
    {
        return [
            'access_token' => $values['access_token'] ?? null,
            'refresh_token' => $values['refresh_token'] ?? ($values['refreshToken'] ?? null),
            'expires_in' => $values['expires_in'] ?? null,
            'raw' => $values,
        ];
    }

    public function userFromToken(string $accessToken): array
    {
        $token = $this->oauthProvider->getAccessToken('bearer', ['access_token' => $accessToken]);
        $owner = $this->oauthProvider->getResourceOwner($token);
        return method_exists($owner,'toArray') ? $owner->toArray() : [];
    }

    public function revokeToken(?string $accessToken = null): bool
    {
        // Optional override in provider
        return false;
    }
}
