<?php

return [

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

    /*
    |--------------------------------------------------------------------------
    | SalesPlay API
    |--------------------------------------------------------------------------
    |
    | base_url is intentionally left blank by default so SalesPlayServiceProvider
    | falls back to the mock client until this is configured. The real
    | SalesPlay Developer API lives at https://api.salesplaypos.com/v1.0
    | (base_url already includes the version — no separate version segment).
    | Per-account API tokens live in salesplay_accounts.api_token (encrypted),
    | never here.
    |
    */

    'salesplay' => [
        'base_url' => env('SALESPLAY_BASE_URL'),
        'timeout' => env('SALESPLAY_TIMEOUT', 30),
    ],

];
