<?php

declare(strict_types=1);

return [

    'default' => env('CHATWORK_CONNECTION', 'default'),

    'base_uri' => env('CHATWORK_BASE_URI', 'https://api.chatwork.com/v2'),

    'timeout' => env('CHATWORK_TIMEOUT', 10),

    'response' => [
        'mode' => 'dto',
    ],

    'connections' => [

        'default' => [
            'auth' => 'api_token',
            'token' => env('CHATWORK_API_TOKEN'),
        ],

    ],

    'oauth' => [
        'client_id' => env('CHATWORK_OAUTH_CLIENT_ID'),
        'client_secret' => env('CHATWORK_OAUTH_CLIENT_SECRET'),
        'redirect_uri' => env('CHATWORK_OAUTH_REDIRECT_URI'),
        'authorization_url' => 'https://www.chatwork.com/packages/oauth2/login.php',
        'token_url' => 'https://oauth.chatwork.com/token',
        'timeout' => env('CHATWORK_OAUTH_TIMEOUT', 10),
        'routes_enabled' => false,
        'route_prefix' => 'chatwork/oauth',
        'redirect_after_callback' => null,
        'token_repository' => null,
        'state_store' => null,
        'refresh_leeway_seconds' => 60,
    ],

];
