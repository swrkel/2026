<?php

return [
    'name' => 'SettlementSW',
    'route_prefix' => 'settlement-sw',
    'asset_public_path' => 'modules/settlementsw',
    'permissions' => require __DIR__ . '/permissions.php',
    'debug_logging' => env('SETTLEMENTSW_DEBUG_LOGGING', false),

    /*
    |--------------------------------------------------------------------------
    | Runtime compatibility keys
    |--------------------------------------------------------------------------
    | Settlement SW still reads historic ERP data classified under the old
    | product module key/database tables. Keep these values configurable here so
    | the active code is module-owned and future DB migration can change them in
    | one place without editing controllers.
    */
    'product_module_key' => 'petro_settlements',
    'subscription_permission_key' => 'enable_settlement_sw_module',
    'legacy_subscription_permission_key' => 'enable_petro_module',

    /*
    |--------------------------------------------------------------------------
    | Legacy-compatible table names
    |--------------------------------------------------------------------------
    | Current tenant DBs still hold Settlement SW shift data in historic table
    | names. Keep those names configurable here instead of hard-coding them in
    | controllers, services, or views.
    */
    'tables' => [
        'daily_shifts' => 'petro_daily_shifts',
        'shifts' => 'petro_shifts',
        'work_shifts' => 'work_shifts',
        'settlements' => 'settlements',
        'products' => 'products',
        'temp_data' => 'temp_data',
        'daily_collections' => 'daily_collections',
        'daily_voucher_items' => 'daily_voucher_items',
        'customer_payments' => 'customer_payments',
        'pump_operator_other_sales' => 'pump_operator_other_sales',
        'pump_operator_meter_sale_details' => 'pump_operator_meter_sale_details',
        'meter_sales' => 'meter_sales',
        'contacts' => 'contacts',
        'contact_groups' => 'contact_groups',
        'customer_references' => 'customer_references',
        'transactions' => 'transactions',
        'transaction_payments' => 'transaction_payments',
        'accounts' => 'accounts',
        'account_groups' => 'account_groups',
        'account_transactions' => 'account_transactions',
        'business' => 'business',
        'business_locations' => 'business_locations',
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy-compatible column names
    |--------------------------------------------------------------------------
    | Keep historical DB column names here instead of hard-coding them in active
    | controllers. When tenant DBs are migrated, this can be changed centrally.
    */
    'columns' => [
        'settlement_reference' => 'petro_settlement_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional VAT integration
    |--------------------------------------------------------------------------
    | Kept behind a module-local adapter. Set regenerate_action to null if the
    | host application does not install/enable VAT regeneration.
    */
    'vat' => [
        'subscription_permission_key' => 'individual_sale',
        'transaction_table' => 'transactions',
        'sale_transaction_type' => 'sell',
        'regenerate_action' => null,
    ],

];
