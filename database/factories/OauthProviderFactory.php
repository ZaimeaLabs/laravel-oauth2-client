<?php

namespace Zaimea\OAuth2Client\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Zaimea\OAuth2Client\Models\OauthProvider;

class OauthProviderFactory extends Factory
{
    protected $model = OauthProvider::class;

    public function definition()
    {
        return [
            'user_id' => 1,
            'provider' => 'github',
            'provider_user_id' => (string)$this->faker->randomNumber(8),
            'access_token' => encrypt('fake-access-token'),
            'refresh_token' => encrypt('fake-refresh-token'),
            'expires_at' => now()->addHour(),
            'scopes' => json_encode(['repo','user']),
            'meta' => json_encode([]),
        ];
    }
}
