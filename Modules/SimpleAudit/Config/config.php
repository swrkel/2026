<?php

return [
    'name' => 'Simple Audit',

    // The module can be mounted inside an existing host layout, but ships with
    // its own fully standalone layout. No host/module view is required.
    'host_layout' => env('SAU_HOST_LAYOUT', 'layouts.app'),

    'route_prefix' => 'superadmin/simple-audit',
    'public_route_prefix' => 'simple-audit',
    'middleware' => ['web', 'auth'],

    // The central database connection is auto-detected if this configured
    // connection does not expose a populated tenants table.
    'central_connection' => env('SAU_CENTRAL_CONNECTION', 'mysql'),
    'runtime_tenant_connection' => 'sau_runtime_tenant',

    // Prefer the host application's dedicated tenant DB connection. Leave null
    // to auto-detect mysql_tenant / Stancl template / other tenant connections.
    'tenant_connection' => env('SAU_TENANT_CONNECTION'),

    // Tenant IDs are registry keys, not always physical database names. The
    // host application's TenancyServiceProvider uses TENANT_DATABASE_PREFIX.
    // SAU_TENANT_DATABASE_PREFIX can override it only for this module.
    'tenant_database_prefix' => env(
        'SAU_TENANT_DATABASE_PREFIX',
        env('TENANT_DATABASE_PREFIX')
    ),

    // Known stancl/tenancy-compatible JSON keys. Additional key aliases are
    // handled by TenantConnectionManager.
    'tenant_data_keys' => [
        'database' => ['tenancy_db_name', 'database', 'db_name', 'database_name'],
        'host' => ['tenancy_db_host', 'host', 'db_host'],
        'port' => ['tenancy_db_port', 'port', 'db_port'],
        'username' => ['tenancy_db_username', 'tenancy_db_user', 'username', 'db_username', 'db_user'],
        'password' => ['tenancy_db_password', 'tenancy_db_pass', 'password', 'db_password', 'db_pass'],
    ],

    'share_expiry_hours' => (int) env('SAU_SHARE_EXPIRY_HOURS', 72),
    'show_store_filter' => (bool) env('SAU_SHOW_STORE_FILTER', true),
    'default_page_length' => (int) env('SAU_DEFAULT_PAGE_LENGTH', 25),

    // Transaction statuses that represent inventory/account affecting purchases.
    'purchase_statuses' => ['received', 'final', 'outright_purchase'],

    // Positive stock adjustment keywords. Any unmatched stock adjustment is
    // treated as a reduction because that matches the legacy stock-adjustment flow.
    'positive_adjustment_keywords' => ['add', 'increase', 'in', 'positive', 'excess'],

    // Fiscal year default. Business-specific fy_start_month is used whenever available.
    'default_fy_start_month' => 4,

    // Public report shares use signed random tokens stored only in sau_ tables.
    'public_share_route' => true,

    'permissions' => [
        'view' => 'simpleaudit.view',
        'export' => 'simpleaudit.export',
        'print' => 'simpleaudit.print',
        'pdf' => 'simpleaudit.pdf',
        'email' => 'simpleaudit.email',
        'whatsapp' => 'simpleaudit.whatsapp',
    ],
];
