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
        'token' => env('POSTMARK_TOKEN'),
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

    'python_api' => [
        'token' => env('API_TOKEN'),
    ],

    'telegram' => [
        'bot_token_fa' => env('TELEGRAM_BOT_TOKEN_FA'),
        'bot_token_en' => env('TELEGRAM_BOT_TOKEN_EN'),
        'bot_username_fa' => env('TELEGRAM_BOT_USERNAME_FA', 'YourFaBot'),
        'bot_username_en' => env('TELEGRAM_BOT_USERNAME_EN', 'YourEnBot'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'broadcast_rate_per_second' => (int) env('TELEGRAM_BROADCAST_RATE', 20),
        'pricing' => [
            'forex' => (float) env('PRICE_FOREX_USDT', 49),
            'crypto' => (float) env('PRICE_CRYPTO_USDT', 49),
            'combo' => (float) env('PRICE_COMBO_USDT', 79),
        ],
    ],

    'blockchain' => [
        'trongrid_api_key' => env('TRONGRID_API_KEY'),
        'bscscan_api_key' => env('BSCSCAN_API_KEY'),
        'auto_confirm' => (bool) env('AUTO_CONFIRM_VERIFIED_TX', true),
        'amount_tolerance' => (float) env('TX_AMOUNT_TOLERANCE', 0.01),
    ],

    // X (Twitter) — auto-post English signal / result copy for ads
    // Create app at https://developer.x.com — needs Read+Write + Access Token
    'twitter' => [
        'enabled' => (bool) env('TWITTER_ENABLED', false),
        'api_key' => env('TWITTER_API_KEY'),
        'api_secret' => env('TWITTER_API_SECRET'),
        'access_token' => env('TWITTER_ACCESS_TOKEN'),
        'access_token_secret' => env('TWITTER_ACCESS_TOKEN_SECRET'),
        'post_signals' => (bool) env('TWITTER_POST_SIGNALS', true),
        'post_results' => (bool) env('TWITTER_POST_RESULTS', true),
        // false = only target_audience=all (recommended for public ads)
        'post_vip' => (bool) env('TWITTER_POST_VIP', false),
        // Optional CTA footer; default builds from TELEGRAM_BOT_USERNAME_EN
        'cta' => env('TWITTER_CTA', ''),
    ],

];
