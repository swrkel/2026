<?php

namespace Modules\FinanceReports\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Middleware\InitializeFinanceTenantContext;
use Modules\FinanceReports\Http\Middleware\ExportFinanceReport;

class RouteServiceProvider extends ServiceProvider
{
    public function map(): void
    {
        /*
         |----------------------------------------------------------------------
         | The reports run against the TENANT database.
         |----------------------------------------------------------------------
         |
         | These routes carried no tenant-context middleware, so every report
         | queried whatever connection happened to be default - the central
         | database - rather than the tenant the operator is logged into. The
         | queries were correct and found nothing, because they were reading the
         | wrong database.
         |
         | That is why the reports stayed empty through three rounds of changes to
         | the filtering, and why the Finance module's own reports behaved
         | differently: Finance registers its routes with this same middleware and
         | FinanceReports did not.
         |
         | InitializeFinanceTenantContext is used rather than Stancl's middleware
         | directly because it already handles both cases this application needs -
         | a tenant domain, and the central domain after login - without rejecting
         | the request on either. Reusing it keeps the two Finance modules
         | initialising tenancy the same way.
         */
        Route::middleware([
            // 'web' MUST come first: it starts the session. With tenancy
            // initialised ahead of it, Authenticate saw no user and sent every
            // report to /login - which lands a signed-in user on /home.
            'web',
            InitializeFinanceTenantContext::class,
            'auth', 'SetSessionData', 'language', 'timezone',
            // Turns any report into CSV, Excel or PDF when ?export= is present.
            ExportFinanceReport::class,
        ])
            ->prefix('finance-reports')
            ->name('finance-reports.')
            ->group(__DIR__ . '/../Routes/web.php');
    }
}
