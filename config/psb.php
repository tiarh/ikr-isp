<?php
/**
 * IKR-ISP config — sesuai jawaban operator (10 klarifikasi).
 * DB shared dgn saleskit, Sanctum auth, multi-teknisi, WA notif, dll.
 */

return [
    'app_name' => env('APP_NAME', 'IKR ISP'),
    'app_url'  => env('APP_URL', 'http://localhost'),

    'default_role' => 'sales', // default user role

    /*
    |--------------------------------------------------------------------------
    | Radius Coverage (default 300m — hardcoded per jawaban #7)
    |--------------------------------------------------------------------------
    */
    'coverage_radius_m' => (int) env('EBILLING_COVERAGE_RADIUS_DEFAULT', 300),

    /*
    |--------------------------------------------------------------------------
    | Saleskit (shared DB + API)
    |--------------------------------------------------------------------------
    */
    'saleskit' => [
        'api_url' => env('SALESKIT_API_URL'),
        'api_key' => env('SALESKIT_API_KEY'),
        'timeout' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | FieldOps (ODP/ODC/OLT + GPS)
    |--------------------------------------------------------------------------
    */
    'fieldops' => [
        'api_url' => env('FIELDOPS_API_URL'),
        'api_key' => env('FIELDOPS_API_KEY'),
        'timeout' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | eBilling (customer sync target + teknisi availability)
    |--------------------------------------------------------------------------
    */
    'ebilling' => [
        'api_url'             => env('EBILLING_API_URL'),
        'api_key'             => env('EBILLING_API_KEY'),
        'teknisi_endpoint'    => env('EBILLING_TEKNISI_ENDPOINT', '/teknisi'),
        'timeout'             => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | OLT ZTE C300 (SSH via phpseclib)
    |--------------------------------------------------------------------------
    */
    'olt' => [
        'c300' => [
            'host'        => env('OLT_C300_SSH_HOST'),
            'port'        => (int) env('OLT_C300_SSH_PORT', 22),
            'user'        => env('OLT_C300_SSH_USER'),
            'password'    => env('OLT_C300_SSH_PASSWORD'),
            'timeout'     => (int) env('OLT_C300_TIMEOUT', 30),
            'prompt_wait' => (int) env('OLT_C300_PROMPT_WAIT', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MikroTik (PPPoE secret add)
    |--------------------------------------------------------------------------
    */
    'mikrotik' => [
        'api_host'     => env('MIKROTIK_API_HOST'),
        'api_user'     => env('MIKROTIK_API_USER'),
        'api_password' => env('MIKROTIK_API_PASSWORD'),
        'api_port'     => (int) env('MIKROTIK_API_PORT', 8728),
        'rest_url'     => env('MIKROTIK_REST_URL'),
        'rest_user'    => env('MIKROTIK_REST_USER'),
        'rest_password'=> env('MIKROTIK_REST_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PPPoE generation
    |--------------------------------------------------------------------------
    | User: {NAME_RTxx_RWxx_ODP}  |  Pass: {nama_router lowercase}
    */
    'pppoe' => [
        'prefix'          => env('PPPOE_PREFIX', 'ikr'),
        'password_length' => (int) env('PPPOE_PASSWORD_LENGTH', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Evolution API (WhatsApp gateway) — notifikasi per status transition
    |--------------------------------------------------------------------------
    */
    'evolution' => [
        'api_url'           => env('EVOLUTION_API_URL'),
        'api_key'           => env('EVOLUTION_API_KEY'),
        'instance'          => env('EVOLUTION_INSTANCE', 'skynet-ikr'),
        'wa_group_teknisi'  => env('EVOLUTION_WA_GROUP_TEKNISI'),
        'wa_group_sales'    => env('EVOLUTION_WA_GROUP_SALES'),
    ],
];
