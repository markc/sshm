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

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ssh' => [
        'home_dir' => env('SSH_HOME_DIR', $_SERVER['HOME'] ?? '/home/user'),
        'default_user' => env('SSH_DEFAULT_USER', 'root'),
        'default_port' => env('SSH_DEFAULT_PORT', 22),
        'default_key_type' => env('SSH_DEFAULT_KEY_TYPE', 'ed25519'),
        'strict_host_checking' => env('SSH_STRICT_HOST_CHECKING', false),
        'timeout' => env('SSH_TIMEOUT', 300), // 5 minutes default timeout
    ],

];
