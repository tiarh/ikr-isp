<?php

return [
    'name' => 'Users',
    'email' => 'laravel@example.com',

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name'    => env('MAIL_FROM_NAME', 'Example'),
    ],

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host'       => env('MAIL_HOST', 'mailpit'),
            'port'       => env('MAIL_PORT', 1025),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username'   => env('MAIL_USERNAME'),
            'password'   => env('MAIL_PASSWORD'),
            'timeout'    => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],
        'log' => [
            'transport' => 'log',
            'channel'   => env('MAIL_LOG_CHANNEL'),
        ],
        'array' => ['transport' => 'array'],
        'failover' => [
            'transport' => 'failover',
            'mailers'   => ['smtp', 'log'],
        ],
    ],
];
