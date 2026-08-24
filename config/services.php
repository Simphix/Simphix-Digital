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

    'palmpay' => [
        'base_url'     => env('BASE_URL_PALMPAY', 'https://open-gw-prod.palmpay-inc.com/'),
        'bearer_token' => env('BEARER_TOKEN'),
        'merchant_id'  => env('MERCHANTID'),
        'version'      => env('VERSION', 'V2.0'),
        'notify_url'   => env('NOTIFY_URL'),
    ],

    'vtpass' => [
        'api_key'         => env('VTPASS_API_KEY'),
        'public_key'      => env('VTPASS_PUBLIC_KEY'),
        'secret_key'      => env('VTPASS_SECRET_KEY'),
        'payment_url'     => env('VTPASS_PAYMENT_URL', 'https://sandbox.vtpass.com/api/pay'),
        'variation_url'   => env('VTPASS_VARIATION_URL', 'https://sandbox.vtpass.com/api/service-variations?serviceID='),
        'verify_jamb_url' => env('VTPASS_VERIFY_JAMB_URL', 'https://sandbox.vtpass.com/api/verify-jamb'),
        'biller_code'     => env('VTPASS_BILLER_CODE'),
    ],

    'arewa' => [
        'base_url'  => env('AREWA_BASE_URL'),
        'api_token' => env('AREWA_API_TOKEN'),
    ],

    'smedata' => [
        'base_url' => env('BASE_URL', 'https://fadeelposdatasub.com.ng/api/data/purchase'),
        'api_key'  => env('API_KEYS'),
    ],

    'smeplug' => [
        'base_url' => env('SMEPLUG_BASE_URL', 'https://smeplug.ng/api/v1'),
        'api_key'  => env('SMEPLUG_API_KEY'),
    ],

    'ninepsb' => [
        'auth_base_url' => env('NINEPSB_AUTH_BASE_URL', 'https://middleware.9psb.com.ng/identity/api/v1'),
        'base_url'      => env('NINEPSB_BASE_URL', 'https://middleware.9psb.com.ng/vas/api/v1'),
        'api_key'       => env('NINEPSB_API_KEY'),
        'secret_key'    => env('NINEPSB_SECRET_KEY'),
        'debit_account' => env('NINEPSB_DEBIT_ACCOUNT'),
    ],

    'amsub' => [
        'base_url' => env('AMSUB_BASE_URL', 'https://amsubapi.com/api'),
        'api_key'  => env('AMSUB_API_KEY'),
    ],

    'webpush' => [
        'vapid' => [
            'subject'     => env('VAPID_SUBJECT', env('APP_URL', 'http://localhost')),
            'public_key'  => env('VAPID_PUBLIC_KEY'),
            'private_key' => env('VAPID_PRIVATE_KEY'),
        ],
    ],
];
