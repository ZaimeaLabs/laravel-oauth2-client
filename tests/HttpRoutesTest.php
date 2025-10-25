<?php

namespace Zaimea\OAuth2Client\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use Zaimea\OAuth2Client\Models\OauthProvider;

class HttpRoutesTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [\Zaimea\OAuth2Client\AuthProvidersServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Use in-memory sqlite
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // create users and oauth_providers tables
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        // run package migration file
        include __DIR__ . '/../database/migrations/2025_10_25_000000_create_oauth_providers_table.php';
        (new \CreateOauthProvidersTable())->up();
    }

    public function test_providers_index_route_requires_auth()
    {
        $response = $this->get(route('oauth2-client.providers.index'));
        $response->assertRedirect(); // to login
    }

    public function test_authenticated_user_sees_providers()
    {
        // create a user
        $userClass = new class extends Model implements \Illuminate\Contracts\Auth\Authenticatable {
            use \Illuminate\Auth\Authenticatable;
            protected $table = 'users';
        };

        $u = $userClass->create(['name' => 'Test', 'email' => 't@example.com']);
        $this->be($u);

        $response = $this->get(route('oauth2-client.providers.index'));
        $response->assertStatus(200);
        $response->assertSee('Connected providers');
    }
}
