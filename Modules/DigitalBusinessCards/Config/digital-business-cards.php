<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Table prefix
    |--------------------------------------------------------------------------
    | Every table this module creates is namespaced so it can never collide
    | with an existing table in the host application.
    */
    'table_prefix' => 'dbc_',

    /*
    |--------------------------------------------------------------------------
    | Database connection
    |--------------------------------------------------------------------------
    | Leave null to use the application default connection.
    */
    'connection' => null,

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    | "admin" is the authenticated management area. "public" is the shareable
    | card URL that lands on a phone after a scan or tap.
    */
    'routes' => [
        'enabled' => true,

        'admin' => [
            'prefix'     => 'business-cards',
            'middleware' => ['web', 'auth'],
            'as'         => 'dbc.',
        ],

        'public' => [
            'prefix'     => 'c',
            'middleware' => ['web'],
            'as'         => 'dbc.public.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ownership
    |--------------------------------------------------------------------------
    | The module stores an owner id only. It never references the host User
    | model, so it works with any guard - or with no auth at all.
    */
    'owner' => [
        'guard'         => null,  // null = default guard
        'scope_to_user' => true,  // users only see their own cards
    ],

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    */
    'media' => [
        'disk'          => 'public',
        'path'          => 'digital-business-cards',
        'max_kilobytes' => 4096,
    ],

    /*
    |--------------------------------------------------------------------------
    | QR code
    |--------------------------------------------------------------------------
    | driver: "auto" detects endroid/qr-code then bacon/bacon-qr-code.
    | Force with "endroid", "bacon", or "none".
    */
    'qr' => [
        'driver' => 'auto',
        'size'   => 512,
        'margin' => 16,
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    | IP addresses are hashed with the app key before storage.
    */
    'analytics' => [
        'enabled'        => true,
        'store_ip_hash'  => true,
        'store_referrer' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'accent_color' => '#2F6F62',
    ],
];
