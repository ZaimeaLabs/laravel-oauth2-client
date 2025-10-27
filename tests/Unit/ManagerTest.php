<?php

namespace Zaimea\OAuth2Client\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Zaimea\OAuth2Client\Core\Manager;
use Zaimea\OAuth2Client\Providers\ProviderAbstract;

class ManagerTest extends TestCase
{
    protected function getPackageProviders($app) {
        return [\Zaimea\OAuth2Client\OAuth2ClientServiceProvider::class];
    }

    public function test_driver_resolves_class()
    {
        config(['oauth2-client.providers.test' => [
            'client_id' => 'x','client_secret' => 'y','redirect' => 'https://app/callback'
        ]]);

        // create a fake provider class for the test
        $class = '\\Zaimea\\OAuth2Client\\Providers\\TestProvider';
        eval('namespace Zaimea\\OAuth2Client\\Providers; class TestProvider extends \\Zaimea\\OAuth2Client\\Providers\\ProviderAbstract { protected function makeProvider(): \\League\\OAuth2\\Client\\Provider\\AbstractProvider { return \\Mockery::mock(\\League\\OAuth2\\Client\\Provider\\AbstractProvider::class); } }');

        $manager = $this->app->make(Manager::class);
        $driver = $manager->driver('test');

        $this->assertInstanceOf(ProviderAbstract::class, $driver);
    }
}
