<?php
namespace Zaimea\OAuth2Client\Providers;

use League\OAuth2\Client\Provider\GenericProvider;

class GoogleProvider extends ProviderAbstract
{
    protected function makeProvider(): \League\OAuth2\Client\Provider\AbstractProvider
    {
        return new GenericProvider([
            'clientId'                => $this->config['client_id'],
            'clientSecret'            => $this->config['client_secret'],
            'redirectUri'             => $this->config['redirect'],
            'urlAuthorize'            => 'https://accounts.google.com/o/oauth2/v2/auth',
            'urlAccessToken'          => 'https://oauth2.googleapis.com/token',
            'urlResourceOwnerDetails' => 'https://openidconnect.googleapis.com/v1/userinfo',
            'scopes'                  => $this->config['scopes'] ?? ['openid', 'profile', 'email']
        ]);
    }

    // Optionally override userFromToken to map fields to your normalized format.
}
