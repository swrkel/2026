<?php

return [
    'name' => 'Enterprise Framework',
    'route_prefix' => 'enterprise-framework',
    'middleware' => ['web', 'auth'],
    'read_only' => true,
    'default_export_formats' => ['pdf', 'excel', 'csv', 'print'],
    'cache_enabled' => env('EFW_REPORT_CACHE', false),
    'cache_ttl_minutes' => env('EFW_REPORT_CACHE_TTL', 30),
    'date_format' => env('EFW_DATE_FORMAT', 'Y-m-d'),
    'datetime_format' => env('EFW_DATETIME_FORMAT', 'Y-m-d H:i:s'),
];
