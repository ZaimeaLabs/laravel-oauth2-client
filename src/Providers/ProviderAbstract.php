<?php
namespace Zaimea\OAuth2Client\Providers;

use League\OAuth2\Client\Token\AccessToken;
use Illuminate\Support\Facades\Log;

/**
 * Abstract base class for all providers.
 *
 * Key responsibilities:
 *  - instantiate underlying league provider (makeProvider)
 *  - create authorization URL (redirectUrl) with optional PKCE
 *  - exchange authorization code for token (getAccessToken)
 *  - normalize token response (formatAccessToken)
 *  - fetch resource owner (userFromToken)
 *
 * Implement makeProvider() in concrete provider class.
 */
abstract class ProviderAbstract
{
    protected array $config;
    protected \League\OAuth2\Client\Provider\AbstractProvider $oauthProvider;

    /**
     * ProviderAbstract constructor.
     * @param array $config provider config as in config/oauth2-client.php
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->oauthProvider = $this->makeProvider();
    }

    /**
     * Build the concrete league provider instance.
     *
     * @return \League\OAuth2\Client\Provider\AbstractProvider
     */
    abstract protected function makeProvider(): \League\OAuth2\Client\Provider\AbstractProvider;

    /**
     * Build authorization redirect info.
     *
     * Returns:
     *  [
     *    'url' => string,                // redirect URL
     *    'code_verifier' => ?string,     // PKCE verifier if generated
     *    'state' => ?string,             // state if provider generates one
     *  ]
     *
     * @param array $options Additional options passed to getAuthorizationUrl (e.g. scope)
     * @return array
     */
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

        return ['url' => $url, 'code_verifier' => $verifier, 'state' => $state];
    }

    /**
     * Exchange authorization code for access token.
     *
     * Normalizes output into array:
     *  [
     *    'access_token' => string|null,
     *    'refresh_token' => string|null,
     *    'expires_in' => int|null,
     *    'raw' => array, // raw response array
     *  ]
     *
     * @param string $code Authorization code
     * @param array $options Optional params (e.g. 'code_verifier' if PKCE)
     * @return array
     */
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
            return $this->formatAccessToken((array)$token);
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            Log::warning('IdentityProviderException during token exchange', [
                'provider' => static::class,
                'message' => $e->getMessage(),
                'response' => method_exists($e,'getResponseBody') ? $e->getResponseBody() : null,
            ]);
            $resp = method_exists($e, 'getResponseBody') ? $e->getResponseBody() : null;
            return $this->formatAccessToken(is_array($resp) ? $resp : []);
        } catch (\Throwable $e) {
            Log::error('Unexpected error getting access token', ['provider'=>static::class,'error'=>$e->getMessage()]);
            return ['access_token'=>null,'refresh_token'=>null,'expires_in'=>null,'raw'=>[]];
        }
    }

    /**
     * Normalize token values returned by league provider / manual exchange.
     *
     * @param array $values
     * @return array
     */
    protected function formatAccessToken(array $values): array
    {
        return [
            'access_token' => $values['access_token'] ?? $values['token'] ?? null,
            'refresh_token' => $values['refresh_token'] ?? ($values['refreshToken'] ?? null),
            'expires_in' => $values['expires_in'] ?? null,
            'raw' => $values,
        ];
    }

    /**
     * Accept AccessToken object or string token and return normalized resource owner data.
     *
     * @param \League\OAuth2\Client\Token\AccessToken|string $accessToken
     * @return array
     */
    public function userFromToken(AccessToken|string $accessToken): array
    {
        try {
            $tokenObj = $accessToken instanceof AccessToken ? $accessToken : new AccessToken(['access_token' => $accessToken]);
            $owner = $this->oauthProvider->getResourceOwner($tokenObj);
            $data = method_exists($owner,'toArray') ? $owner->toArray() : (array)$owner;
            return $this->normalizeResourceOwner($data);
        } catch (\Throwable $e) {
            Log::error('userFromToken failed', ['provider'=>static::class,'error'=>$e->getMessage()]);
            return [];
        }
    }

    /**
     * Optional normalizer for resource owner to unify keys across providers.
     *
     * @param array $data
     * @return array
     */
    protected function normalizeResourceOwner(array $data): array
    {
        return $data;
    }

    /**
     * Generate a PKCE code verifier string.
     *
     * @param int $length default 64
     * @return string
     */
    protected function generateCodeVerifier(int $length = 64): string
    {
        $bytes = random_bytes($length);
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /**
     * Create code challenge from verifier (S256).
     *
     * @param string $verifier
     * @return string
     */
    protected function codeChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /**
     * Access to raw provider config.
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Optional provider-specific token revocation (override in provider)
     *
     * @param string|null $accessToken
     * @return bool success
     */
    public function revokeToken(?string $accessToken = null): bool
    {
        return false;
    }

    public function refreshAccessToken(string $refreshToken, array $options = []): array
    {
        $params = array_merge(['refresh_token' => $refreshToken], $options);
        try {
            $token = $this->oauthProvider->getAccessToken('refresh_token', $params);
            if ($token instanceof AccessToken) {
                return $this->formatAccessToken($token->getValues());
            }
            return $this->formatAccessToken((array)$token);
        } catch (\Throwable $e) {
            Log::error('refreshAccessToken failed', ['provider' => static::class, 'error' => $e->getMessage()]);
            return ['access_token' => null, 'refresh_token' => null, 'expires_in' => null, 'raw' => []];
        }
    }
}
