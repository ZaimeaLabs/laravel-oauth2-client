<?php

namespace Zaimea\OAuth2Client\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Models\User; // in testbench you can create a lightweight user model

class ConnectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app) {
        return [\Zaimea\OAuth2Client\OAuth2ClientServiceProvider::class];
    }

    protected function setUp(): void {
        parent::setUp();
        // run package migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    public function test_redirect_and_callback_flow_saves_provider()
    {
        // create user and act as them
        $user = User::factory()->create();
        $this->actingAs($user);

        config(['oauth2-client.providers.github' => [
            'client_id' => 'cid','client_secret' => 'csec','redirect' => 'https://accounts.1s100.online/oauth2-client/connect/github/callback'
        ]]);

        // call redirect to get session state etc.
        $resp = $this->get(route('oauth2-client.connect','github'));
        $resp->assertStatus(302);

        // simulate callback with code (we'll fake manual exchange)
        Http::fake([
            'https://github.com/login/oauth/access_token' => Http::response(['access_token' => 'gho_xxx','scope'=>'repo,user:email','token_type'=>'bearer'], 200),
            'https://api.github.com/user' => Http::response(['id'=>123,'login'=>'testuser','email'=>'user@example.com'], 200),
        ]);

        // pull the state from session
        $state = session('oauth.github.state'); // if saved by redirect
        $callbackUrl = route('oauth2-client.callback',['provider'=>'github']);
        $response = $this->get($callbackUrl . '?code=abc123&state=' . $state);

        $response->assertRedirect(route('oauth2-client.providers.index'));

        $this->assertDatabaseHas('oauth_providers', [
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_user_id' => 123,
        ]);
    }
}
