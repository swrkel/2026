<?php
return [
    'name' => 'Rice Mill Module',
    'route_prefix' => 'rice-mill',
    'middleware' => ['web', 'tenant.context', 'auth', 'rcm.context'],
    'finance_handler' => env('RCM_FINANCE_HANDLER'),
    'currency_decimals' => 4,
    'quantity_decimals' => 3,
    'date_format' => 'Y-m-d',
];
