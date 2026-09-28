<?php

return [
    'module_name' => 'Finance Reports',
    'route_prefix' => 'finance-reports',
    'read_only' => true,
    'default_date_mode' => 'current_month',
    'branch_modes' => ['location', 'consolidated'],
    'exports' => [
        'pdf' => true,
        'excel' => true,
        'csv' => true,
        'print' => true,
    ],
    'cache' => [
        'enabled' => true,
        'ttl_minutes' => 15,
    ],
    'large_report_limit' => 5000,
];
