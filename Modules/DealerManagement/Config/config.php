<?php
return [
    'route_prefix' => 'dealer-management',
    'portal_prefix' => 'dealer',
    'session_key' => 'dealer_management_user_id',
    'business_session_keys' => ['business.id', 'business_id'],
    'stale_stock_days' => 3,
    'default_stock_cover_days' => 7,
    'distribution_module_key' => 'distribution_new',
    'login_rate_limit_per_minute' => 10,
    'hub_session_key' => 'dealer_hub_user_id',
    'hub_central_database' => env('DEALER_HUB_CENTRAL_DATABASE', env('DB_DATABASE')),
    'hub_default_allocation' => 'fifo',
    'hub_login_rate_limit_per_minute' => 10,
];
