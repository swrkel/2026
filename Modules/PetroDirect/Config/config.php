<?php

return [
    'name' => 'PetroDirect',
    'route_prefix' => 'petrodirect',
    'route_name_prefix' => 'petrodirect.',
    'require_shift_selection' => false,
    // Keep verbose controller diagnostics disabled on production by default.
    'debug_logging' => env('PETRODIRECT_DEBUG_LOGGING', false),
    'excluded_features' => [
    ],
    'allowed_shared_tables' => [
        'users',
        'business',
        'business_locations',
        'products',
        'contacts',
        'transactions',
        'transaction_payments',
        'accounts',
        'account_transactions',
        'currencies',
        'tax_rates',
        'settlements',
        'pumps',
        'fuel_tanks',
        'meter_sales',
        'tank_transfers',
        'dip_readings',
        'petro_shifts',
        'pump_operator_assignments',
    ],
];
