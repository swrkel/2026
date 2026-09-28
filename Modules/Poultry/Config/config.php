<?php

return [
    'name' => 'Poultry',

    'module_version' => '1.0',

    /*
    |--------------------------------------------------------------------------
    | Superadmin package id
    |--------------------------------------------------------------------------
    | Assign the pid allocated by the Superadmin module. Left null until then -
    | the module still boots, it simply is not gated by a package.
    */
    'pid' => null,

    /*
    |--------------------------------------------------------------------------
    | Shared core tables
    |--------------------------------------------------------------------------
    | No code lives outside Modules/Poultry, but the module shares the ERP's
    | existing tables so suppliers, customers, products and stock are never
    | duplicated. Names are configurable in case a deployment renames one.
    */
    'shared_tables' => [
        'contacts'                   => 'contacts',
        'products'                   => 'products',
        'variations'                 => 'variations',
        'variation_location_details' => 'variation_location_details',
        'business_locations'         => 'business_locations',
        'units'                      => 'units',
        'transactions'               => 'transactions',
        'users'                      => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction types written to the shared transactions table
    |--------------------------------------------------------------------------
    | transactions.type is a plain indexed string(255) in this schema, so these
    | need no core migration. Verified against Finance, FinanceReports,
    | ManagementReport, StockReports and app/Utils: every one filters
    | transaction type by explicit whitelist, never by negative match, so these
    | rows cannot leak into existing report output.
    */
    'transaction_types' => [
        'feed_issue' => 'poultry_feed_issue',
        'production' => 'poultry_production',
        'harvest'    => 'poultry_harvest',
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration switches
    |--------------------------------------------------------------------------
    | When false the module records against its own tables only and never
    | touches shared stock or the ledger. Useful for pilot runs and for
    | tenants that have not licensed Product / Finance.
    */
    'post_to_stock'  => true,
    'post_to_ledger' => true,

    /*
    |--------------------------------------------------------------------------
    | Operational defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'point_of_lay_week'   => 18,
        'laying_cycle_weeks'  => 72,
        'egg_tray_size'       => 30,
        'weight_sample_size'  => 100,
        'mortality_alert_pct' => 1.0,
    ],
];
