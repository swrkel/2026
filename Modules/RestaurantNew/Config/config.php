<?php

return [
    'name' => 'RestaurantNew',
    'display_name' => 'Restaurant-New',
    'route_prefix' => 'restaurant-new',
    'permission_prefix' => 'restaurant_new',
    'date_format' => 'Y-m-d',
    'time_format' => 'H:i',
    'pos_design_standard' => true,
    'standalone' => true,
    'multi_tenant' => true,
    'multi_business' => true,
    'modules' => [
        'dashboard' => true,
        'settings' => true,
        'menu' => true,
        'tables' => true,
        'orders' => true,
        'kot' => true,
        'billing' => true,
        'delivery' => true,
        'reports' => true,
    ],
];
