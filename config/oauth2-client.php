<?php

return [
    'default' => env('OAUTH2_CLIENT_DEFAULT', null),
    'providers' => [
        'github' => [
            'driver' => 'github',
            'client_id' => env('GITHUB_CLIENT_ID'),
            'client_secret' => env('GITHUB_CLIENT_SECRET'),
            'redirect' => env('GITHUB_REDIRECT'),
            'scopes' => ['repo','user:email'],
            'use_pkce' => false,
        ],
        'google' => [
            'driver' => 'google',
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('GOOGLE_REDIRECT'),
            'scopes' => ['openid','profile','email','https://www.googleapis.com/auth/calendar.events'],
        ],
        'facebook' => [
            'driver' => 'facebook',
            'client_id' => env('FACEBOOK_CLIENT_ID'),
            'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
            'redirect' => env('FACEBOOK_REDIRECT'),
            'scopes' => ['email','public_profile'],
        ],
        'x' => [
            'driver' => 'x',
            'client_id' => env('X_CLIENT_ID'),
            'client_secret' => env('X_CLIENT_SECRET'),
            'redirect' => env('X_REDIRECT'),
            'scopes' => ['tweet.read','users.read'],
        ],
        'instagram' => [
            'driver' => 'instagram',
            'client_id' => env('INSTAGRAM_CLIENT_ID'),
            'client_secret' => env('INSTAGRAM_CLIENT_SECRET'),
            'redirect' => env('INSTAGRAM_REDIRECT'),
            'scopes' => ['user_profile'],
        ],
        'youtube' => [
            'driver' => 'youtube',
            'client_id' => env('YOUTUBE_CLIENT_ID'),
            'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
            'redirect' => env('YOUTUBE_REDIRECT'),
            'scopes' => ['https://www.googleapis.com/auth/youtube.readonly'],
        ],
    ],
];
