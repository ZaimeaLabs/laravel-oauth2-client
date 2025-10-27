<?php
return [
    'providers' => [
        'github' => [
            'client_id' => env('GITHUB_CLIENT_ID'),
            'client_secret' => env('GITHUB_CLIENT_SECRET'),
            'redirect' => env('GITHUB_REDIRECT'),
            'scopes' => ['repo', 'user:email'],
            'use_pkce' => false,
        ],
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('GOOGLE_REDIRECT'),
            'scopes' => ['openid', 'profile', 'email', 'https://www.googleapis.com/auth/calendar.events'],
            'use_pkce' => true,
        ],
        // add others similarly
    ],
    // optional overall settings
    'encrypt_tokens' => true, // encrypt access_token and refresh_token in DB
];
