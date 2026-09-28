<?php

use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Stancl\Tenancy\Middleware\ScopeSessions;

return [
    'name' => 'Audit',
    'route_prefix' => 'audit',
    'middleware' => [
        'web',
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
        ScopeSessions::class,
        'IsInstalled',
        'bootstrap',
        'language',
        'dynamic.no-store',
        'auth',
        'SetSessionData',
        'timezone',
        'tenant.context',
        'check.route.permission',
    ],
    'permission_prefix' => 'audit',
    'fiscal_year_start_month' => 1,
    'chunk_size' => 500,
    'amount_tolerance' => 0.005,
    'default_date_preset' => 'this_year',
    'auto_resolve_missing_findings' => false,
    'notifications' => false,
    'stale_run_minutes' => 60,
    'route_integrity' => [
        'max_routes' => 5000,
        'max_findings' => 250,
    ],
    'central' => [
        'max_run_jobs' => 250,
        'max_report_rows' => 20000,
        'max_report_rows_per_source' => 5000,
    ],
    'scheduled' => [
        'enabled' => env('AUDIT_SCHEDULED_ENABLED', true),
        'daily_time' => '01:30',
    ],
];
