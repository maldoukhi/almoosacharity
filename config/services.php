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
    | Messaging (SMS + WhatsApp) — Phase 5
    |--------------------------------------------------------------------------
    |
    | Sender name/template management lives in an in-app settings screen
    | (later phase); only secrets and the active driver selection live
    | here / in .env, per CLAUDE.md.
    */

    'sms' => [
        'driver' => env('SMS_DRIVER', 'fake'), // taqnyat|fake
    ],

    'taqnyat' => [
        'api_key' => env('TAQNYAT_API_KEY'),
        'sender' => env('TAQNYAT_SENDER'),
    ],

    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'fake'), // okta|fake
    ],

    'okta_connect' => [
        'base_url' => env('OKTA_CONNECT_BASE_URL'),
        'token' => env('OKTA_CONNECT_TOKEN'),
        'channel_id' => env('OKTA_CONNECT_CHANNEL_ID'),
    ],

];
