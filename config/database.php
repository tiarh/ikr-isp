<?php

return [
    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [
        // ── PRIMARY: IKR-ISP (psb_orders, psb_status_logs, dll) ──
        'mysql' => [
            'driver'   => 'mysql',
            'host'     => env('DB_HOST', '127.0.0.1'),
            'port'     => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'ikr_isp'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset'  => 'utf8mb4',
            'collation'=> 'utf8mb4_unicode_ci',
            'prefix'   => '',
            'prefix_indexes' => true,
            'strict'   => true,
            'engine'   => 'InnoDB',
        ],

        // ── SALESKIT (shared) — read-write untuk table registrations ──
        'saleskit' => [
            'driver'   => 'mysql',
            'host'     => env('SALESKIT_DB_HOST', '127.0.0.1'),
            'port'     => env('SALESKIT_DB_PORT', '3306'),
            'database' => env('SALESKIT_DB_DATABASE', 'skynet_saleskit'),
            'username' => env('SALESKIT_DB_USERNAME'),
            'password' => env('SALESKIT_DB_PASSWORD'),
            'charset'  => 'utf8mb4',
            'collation'=> 'utf8mb4_unicode_ci',
            'prefix'   => '',
            'prefix_indexes' => true,
            'strict'   => true,
            'engine'   => 'InnoDB',
        ],

        // ── EBILLING (read-only) — teknisi list + open ticket count ──
        'ebilling' => [
            'driver'   => 'mysql',
            'host'     => env('EBILLING_DB_HOST', 'billing.sky.net.id'),
            'port'     => env('EBILLING_DB_PORT', '3306'),
            'database' => env('EBILLING_DB_DATABASE', 'ebilling'),
            'username' => env('EBILLING_DB_USERNAME'),
            'password' => env('EBILLING_DB_PASSWORD'),
            'charset'  => 'utf8mb4',
            'collation'=> 'utf8mb4_unicode_ci',
            'prefix'   => '',
            'prefix_indexes' => true,
            'strict'   => true,
            'engine'   => 'InnoDB',
            // read-only: enforce di level app
            'options'  => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ],
        ],
    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [
        'client' => env('REDIS_CLIENT', 'predis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix'  => env('REDIS_PREFIX', 'ikr_isp_database_'),
        ],
        'default' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],
];
