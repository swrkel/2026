<?php

return [
    'name' => 'PetroDirectNew',
    'route_prefix' => 'petro-direct-new',
    'route_name_prefix' => 'petro-direct-new.',
    'table_prefix' => 'pdirectnew_',
    'currency_decimals' => 4,
    'meter_decimals' => 3,
    'require_shift_for_settlement' => false,
    'compatibility_prefixes' => ['petrodirectnew', 'petro-directnew'],
    'shared_master_tables' => [
        'business', 'business_locations', 'users', 'contacts', 'products',
        'variations', 'accounts', 'tax_rates', 'currencies', 'stores',
    ],
];
