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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.1-flash-lite'),
    ],

    'trackingmore' => [
        'api_key' => env('TRACKINGMORE_API_KEY'),
        'webhook_secret' => env('TRACKINGMORE_WEBHOOK_SECRET'),
    ],

    'jtexpress' => [
        'api_key'       => env('JTEXPRESS_API_KEY'),
        'customer_code' => env('JTEXPRESS_CUSTOMER_CODE'),
        'api_url'       => env('JTEXPRESS_API_URL', 'https://openapi.jtexpress.ph'),
    ],

    'flashexpress' => [
        'mch_id'   => env('FLASHEXPRESS_MCH_ID'),
        'sign_key' => env('FLASHEXPRESS_SIGN_KEY'),
        'api_url'  => env('FLASHEXPRESS_API_URL', 'https://open-api.flashexpress.ph'),
    ],

    'lbc' => [
        'partner_token' => env('LBC_PARTNER_TOKEN'),
        'secret_key'    => env('LBC_SECRET_KEY'),
        'account_no'    => env('LBC_ACCOUNT_NO'),
        'api_url'       => env('LBC_API_URL', 'https://api.lbcexpress.com'),
    ],

];
