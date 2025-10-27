<?php

namespace Zaimea\OAuth2Client\Models;

use Illuminate\Database\Eloquent\Model;

class OauthProvider extends Model
{
    protected $table = 'oauth_providers';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'scopes',
        'meta'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'scopes' => 'array',
        'meta' => 'array',
    ];
}
