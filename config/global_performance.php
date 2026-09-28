<?php

return [
    'enabled' => env('GLOBAL_PERFORMANCE_ENABLED', true),
    'schema_ttl' => (int) env('GLOBAL_PERFORMANCE_SCHEMA_TTL', 600),
    'module_registry_ttl' => (int) env('GLOBAL_PERFORMANCE_MODULE_TTL', 300),
    'subscription_ttl' => (int) env('GLOBAL_PERFORMANCE_SUBSCRIPTION_TTL', 60),
    'sidebar_ttl' => (int) env('GLOBAL_PERFORMANCE_SIDEBAR_TTL', 300),
    'datatable_search_delay' => (int) env('GLOBAL_PERFORMANCE_DATATABLE_SEARCH_DELAY', 350),
];
