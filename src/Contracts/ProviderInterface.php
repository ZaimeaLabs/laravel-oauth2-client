<?php

namespace Zaimea\OAuth2Client\Contracts;

interface ProviderInterface
{
    public function redirectUrl(): string;

    public function getAccessToken(string $code): array;

    public function refreshAccessToken(string $refreshToken): array;

    public function userFromToken(string $accessToken): array;
    
    public function revokeToken(?string $accessToken = null): bool;
}
