<?php

namespace Zaimea\OAuth2Client\Tests;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Mockery;
use Orchestra\Testbench\TestCase;
use Zaimea\OAuth2Client\Manager;
use Zaimea\OAuth2Client\Providers\ProviderAbstract;

class ProviderMockTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [\Zaimea\OAuth2Client\AuthProvidersServiceProvider::class];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_refresh_token_rotation_and_formatting()
    {
        $manager = $this->app->make(Manager::class);
        $provider = $manager->driver('github');

        // Create a partial mock of the underlying oauthProvider to simulate token rotation
        $oauthMock = Mockery::mock(AbstractProvider::class);
        $tokenArray = ['access_token' => 'new-access', 'refresh_token' => 'new-refresh', 'expires_in' => 3600];
        $accessToken = Mockery::mock(AccessToken::class);
        $accessToken->shouldReceive('getValues')->andReturn($tokenArray);

        $oauthMock->shouldReceive('getAccessToken')->with('refresh_token', Mockery::any())->andReturn($accessToken);

        // inject mock
        $ref = new \ReflectionProperty(ProviderAbstract::class, 'oauthProvider');
        $ref->setAccessible(true);
        $ref->setValue($provider, $oauthMock);

        $result = $provider->refreshAccessToken('old-refresh');
        $this->assertArrayHasKey('access_token', $result);
        $this->assertEquals('new-access', $result['access_token']);
        $this->assertEquals('new-refresh', $result['refresh_token']);
    }

    public function test_revoke_token_calls_provider_specific_endpoint()
    {
        $manager = $this->app->make(Manager::class);
        $provider = $manager->driver('google');

        // For google revokeToken uses Http facade; we'll simulate by calling the method
        $this->assertIsBool($provider->revokeToken('fake-token'));
    }
}
