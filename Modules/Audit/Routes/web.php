<?php

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Stancl\Tenancy\Middleware\ScopeSessions;

/*
|--------------------------------------------------------------------------
| Central Audit routes
|--------------------------------------------------------------------------
| Register exact-domain central routes BEFORE the tenant routes. This lets
| the existing /audit sidebar URL work on both central and tenant hosts:
| central hosts match these domain-bound routes, tenant hosts skip them and
| continue to the existing tenant-safe route group below.
*/
$centralDomains = array_values(array_unique(array_filter(array_map(function ($domain) {
    return trim((string) $domain);
}, (array) config('tenancy.central_domains', [])))));

if (!$centralDomains) {
    $fallbackCentralHost = parse_url((string) config('app.url'), PHP_URL_HOST);
    if ($fallbackCentralHost) {
        $centralDomains[] = $fallbackCentralHost;
    }
}

$centralMiddleware = [
    'web',
    'auth',
    'language',
    'dynamic.no-store',
    'audit.central',
];

foreach ($centralDomains as $centralDomain) {
    Route::domain($centralDomain)->group(function () use ($centralMiddleware) {
        Route::group([
            'prefix' => config('audit.route_prefix', 'audit'),
            'middleware' => $centralMiddleware,
        ], function () {
            $controller = 'Modules\\Audit\\Http\\Controllers\\CentralAuditController';

            Route::get('/', $controller . '@dashboard')->middleware('audit.permission:audit.view');
            Route::get('/run', $controller . '@run')->middleware('audit.permission:audit.run');
            Route::post('/run', $controller . '@storeRun')->middleware('audit.permission:audit.run');
            Route::get('/findings', $controller . '@findings')->middleware('audit.permission:audit.findings.view');
            Route::get('/rules', $controller . '@rules')->middleware('audit.permission:audit.rules.manage');
            Route::get('/schedules', $controller . '@schedules')->middleware('audit.permission:audit.schedules.manage');
            Route::get('/reports', $controller . '@reports')->middleware('audit.permission:audit.reports.view');
            Route::get('/scope-options', $controller . '@scopeOptions')->middleware('audit.permission:audit.view');
            Route::get('/reports/export/{format}', $controller . '@export')
                ->middleware('audit.permission:audit.reports.export')
                ->where('format', 'csv|excel|pdf|print');
        });
    });
}

/*
|--------------------------------------------------------------------------
| Existing tenant Audit routes (kept unchanged)
|--------------------------------------------------------------------------
*/
$defaultMiddleware = [
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
];

Route::group([
    'prefix' => config('audit.route_prefix', 'audit'),
    'as' => 'audit.',
    'middleware' => config('audit.middleware', $defaultMiddleware),
], function () {
    Route::get('/', 'Modules\\Audit\\Http\\Controllers\\DashboardController@index')
        ->middleware('audit.permission:audit.view')->name('dashboard');
    Route::get('/run', 'Modules\\Audit\\Http\\Controllers\\RunAuditController@index')
        ->middleware('audit.permission:audit.run')->name('run.index');
    Route::post('/run', 'Modules\\Audit\\Http\\Controllers\\RunAuditController@store')
        ->middleware('audit.permission:audit.run')->name('run.store');
    Route::get('/findings', 'Modules\\Audit\\Http\\Controllers\\FindingController@index')
        ->middleware('audit.permission:audit.findings.view')->name('findings.index');
    Route::get('/findings/{finding}', 'Modules\\Audit\\Http\\Controllers\\FindingController@show')
        ->middleware('audit.permission:audit.findings.view')->name('findings.show');
    Route::post('/findings/{finding}/status', 'Modules\\Audit\\Http\\Controllers\\ResolutionController@update')
        ->middleware('audit.permission:audit.findings.resolve')->name('findings.status');
    Route::get('/rules', 'Modules\\Audit\\Http\\Controllers\\RuleController@index')
        ->middleware('audit.permission:audit.rules.manage')->name('rules.index');
    Route::post('/rules', 'Modules\\Audit\\Http\\Controllers\\RuleController@update')
        ->middleware('audit.permission:audit.rules.manage')->name('rules.update');
    Route::get('/schedules', 'Modules\\Audit\\Http\\Controllers\\ScheduleController@index')
        ->middleware('audit.permission:audit.schedules.manage')->name('schedules.index');
    Route::post('/schedules', 'Modules\\Audit\\Http\\Controllers\\ScheduleController@store')
        ->middleware('audit.permission:audit.schedules.manage')->name('schedules.store');
    Route::post('/schedules/{schedule}/toggle', 'Modules\\Audit\\Http\\Controllers\\ScheduleController@toggle')
        ->middleware('audit.permission:audit.schedules.manage')->name('schedules.toggle');
    Route::get('/reports', 'Modules\\Audit\\Http\\Controllers\\ReportController@index')
        ->middleware('audit.permission:audit.reports.view')->name('reports.index');
    Route::get('/reports/export/{format}', 'Modules\\Audit\\Http\\Controllers\\ReportController@export')
        ->middleware('audit.permission:audit.reports.export')
        ->where('format', 'csv|excel|pdf|print')->name('reports.export');
});
