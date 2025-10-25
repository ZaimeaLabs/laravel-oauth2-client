<?php

namespace Zaimea\OAuth2Client\Tests;

use Orchestra\Testbench\TestCase;
use Zaimea\OAuth2Client\Manager;

class ManagerTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [\Zaimea\OAuth2Client\AuthProvidersServiceProvider::class];
    }

    public function test_driver_resolution()
    {
        $manager = $this->app->make(Manager::class);
        $this->assertIsObject($manager);
    }
}
