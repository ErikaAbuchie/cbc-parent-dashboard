<?php

return [
    'ollama' => [
        'enabled' => env('OLLAMA_ENABLED', true),
        'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'phi3:mini'),
    ],
    'africastalking' => [
        'enabled' => env('AT_SMS_ENABLED', false),
        'username' => env('AT_USERNAME', 'sandbox'),
        'key' => env('AT_API_KEY'),
    ],
    'parent_access' => [
        'frontend_url' => env('PARENT_FRONTEND_URL', 'http://localhost:3000'),
        'proxy_secret' => env('PARENT_PROXY_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
