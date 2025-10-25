<?php

namespace Zaimea\OAuth2Client\Tests;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Mockery;
use Orchestra\Testbench\TestCase;
use Zaimea\OAuth2Client\Manager;
use Zaimea\OAuth2Client\Providers\ProviderAbstract;

class ProviderMockExtendedTest extends TestCase
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

    public function providerMockCommonRefresh(string $driver)
    {
        $manager = $this->app->make(Manager::class);
        $provider = $manager->driver($driver);

        $oauthMock = Mockery::mock(AbstractProvider::class);
        $tokenArray = ['access_token' => 'rotated-access', 'refresh_token' => 'rotated-refresh', 'expires_in' => 3600];
        $accessToken = Mockery::mock(AccessToken::class);
        $accessToken->shouldReceive('getValues')->andReturn($tokenArray);

        $oauthMock->shouldReceive('getAccessToken')->with('refresh_token', Mockery::any())->andReturn($accessToken);

        $ref = new \ReflectionProperty(ProviderAbstract::class, 'oauthProvider');
        $ref->setAccessible(true);
        $ref->setValue($provider, $oauthMock);

        $result = $provider->refreshAccessToken('old-refresh');
        $this->assertArrayHasKey('access_token', $result);
        $this->assertEquals('rotated-access', $result['access_token']);
    }

    public function test_github_refresh_rotation()
    {
        $this->providerMockCommonRefresh('github');
    }

    public function test_google_refresh_rotation()
    {
        $this->providerMockCommonRefresh('google');
    }

    public function test_facebook_refresh_rotation()
    {
        $this->providerMockCommonRefresh('facebook');
    }

    public function test_x_refresh_rotation()
    {
        $this->providerMockCommonRefresh('x');
    }

    public function test_instagram_refresh_rotation()
    {
        $this->providerMockCommonRefresh('instagram');
    }

    public function test_revoke_token_methods_return_bool()
    {
        $manager = $this->app->make(Manager::class);
        $this->assertIsBool($manager->driver('github')->revokeToken('t'));
        $this->assertIsBool($manager->driver('google')->revokeToken('t'));
        $this->assertIsBool($manager->driver('facebook')->revokeToken('t'));
        $this->assertIsBool($manager->driver('x')->revokeToken('t'));
        $this->assertIsBool($manager->driver('instagram')->revokeToken('t'));
    }
}
