<?php

namespace Zaimea\OAuth2Client\Contracts;

interface ProviderInterface
{
    /**
     * Generate authorization redirect data.
     *
     * @param  array  $options
     * @return array{url: string, code_verifier: ?string}
     */
    public function redirectUrl(): array;

    public function getAccessToken(string $code): array;

    public function refreshAccessToken(string $refreshToken): array;

    public function userFromToken(string $accessToken): array;

    public function revokeToken(?string $accessToken = null): bool;
}
