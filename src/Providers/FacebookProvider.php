<?php
namespace Zaimea\OAuth2Client\Providers;

use League\OAuth2\Client\Provider\GenericProvider;

class FacebookProvider extends ProviderAbstract
{
    protected function makeProvider(): \League\OAuth2\Client\Provider\AbstractProvider
    {
        return new GenericProvider([
            'clientId'                => $this->config['client_id'],
            'clientSecret'            => $this->config['client_secret'],
            'redirectUri'             => $this->config['redirect'],
            'urlAuthorize'            => 'https://www.facebook.com/v12.0/dialog/oauth',
            'urlAccessToken'          => 'https://graph.facebook.com/v12.0/oauth/access_token',
            'urlResourceOwnerDetails' => 'https://graph.facebook.com/me?fields=id,name,email,picture',
            'scopes'                  => $this->config['scopes'] ?? ['email', 'public_profile']
        ]);
    }
}
