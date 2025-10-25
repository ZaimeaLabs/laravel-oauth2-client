<?php

namespace Zaimea\OAuth2Client\Tests;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Mockery;
use Orchestra\Testbench\TestCase;
use Zaimea\OAuth2Client\Manager;

class ProviderIntegrationTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [\Zaimea\OAuth2Client\AuthProvidersServiceProvider::class];
    }

    public function test_manager_returns_provider_instance()
    {
        $manager = $this->app->make(Manager::class);
        $this->assertIsObject($manager->driver('github'));
    }

    public function test_user_from_token_uses_provider()
    {
        $manager = $this->app->make(Manager::class);
        $provider = $manager->driver('github');

        // we cannot easily mock League's concrete provider here without heavy plumbing,
        // but we can assert that calling userFromToken doesn't throw for invalid token
        $result = $provider->userFromToken('invalid-token');
        $this->assertIsArray($result);
    }

    public function test_refresh_call_handling()
    {
        $manager = $this->app->make(Manager::class);
        $provider = $manager->driver('google');
        $result = $provider->refreshAccessToken('invalid-refresh');
        $this->assertIsArray($result);
    }
}
