<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default LDAP Connection Name
    |--------------------------------------------------------------------------
    */

    'default' => env('LDAP_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | LDAP Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [

        'default' => [
            'hosts'            => [env('LDAP_HOST', '127.0.0.1')],
            'username'         => env('LDAP_USERNAME', ''),
            'password'         => env('LDAP_PASSWORD', ''),
            'port'             => env('LDAP_PORT', 389),
            'base_dn'          => env('LDAP_BASE_DN', ''),
            'timeout'          => env('LDAP_TIMEOUT', 5),
            'use_ssl'          => env('LDAP_SSL', false),
            'use_tls'          => env('LDAP_TLS', false),
            'use_sasl'         => env('LDAP_SASL', false),
            'sasl_options'     => [],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | LDAP Logging
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'enabled'   => env('LDAP_LOGGING', true),
        'channel'   => env('LOG_CHANNEL', 'stack'),
        'level'     => 'info',
        'detailed'  => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | LDAP Cache
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'enabled'   => false,
        'driver'    => 'file',
    ],

    'allowed_group' => env('LDAP_ALLOWED_GROUP', 'Monitoring Medrec'),

];
