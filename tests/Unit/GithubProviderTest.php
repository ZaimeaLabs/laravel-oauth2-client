<?php

namespace Zaimea\OAuth2Client\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Http;
use Zaimea\OAuth2Client\Providers\GithubProvider;

class GithubProviderTest extends TestCase
{
    protected function getPackageProviders($app) {
        return [\Zaimea\OAuth2Client\OAuth2ClientServiceProvider::class];
    }

    public function test_manual_exchange_fallback()
    {
        $config = [
            'client_id' => 'cid',
            'client_secret' => 'csecret',
            'redirect' => 'https://accounts.1s100.online/oauth2-client/connect/github/callback',
        ];

        // Simulate League returning no access token by having parent->getAccessToken return []
        // We'll construct provider and call getAccessToken which will attempt manual exchange
        $provider = new GithubProvider($config);

        // fake http response for manual exchange
        Http::fake([
            'https://github.com/login/oauth/access_token' => Http::response([
                'access_token' => 'gho_abcdef',
                'scope' => 'repo,user:email',
                'token_type' => 'bearer',
            ], 200)
        ]);

        $result = $provider->getAccessToken('somecode', []);
        $this->assertEquals('gho_abcdef', $result['access_token']);
    }
}
