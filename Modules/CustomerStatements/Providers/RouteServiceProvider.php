<?php

namespace Modules\CustomerStatements\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\CustomerStatements\Http\Controllers\CustomerStatementController;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register all Customer Statements routes using Laravel's current route
     * provider API.  Do not rely on the legacy map() method: Laravel no longer
     * invokes it automatically for this provider.
     */
    public function boot(): void
    {
        $this->routes(function (): void {
            /* LA-1152: 'tenant.context' MUST run before 'auth'.

                 SetTenantContext calls tenancy()->initialize(), which switches the
                 default database connection to the tenant. With 'auth' ahead of it,
                 Laravel resolves the signed-in user against the connection active at
                 that moment, and every later auth()->user() lookup hits the tenant
                 database instead - returning a DIFFERENT row carrying the same
                 numeric id. That is why the header showed another user's name on
                 these module pages, and why the location dropdowns resolved wrongly.

                 app/Providers/RouteServiceProvider.php line 119 already has the
                 correct order; these module providers did not. */
            $middleware = [
                'web',
                'tenant.context',
                'auth',
                'language',
                'SetSessionData',
                'DayEnd',
            ];

            Route::middleware($middleware)
                ->prefix('customer-statements')
                ->as('customerstatements.')
                ->group(module_path('CustomerStatements', 'Routes/web.php'));

            // Backward-compatible URLs used by older sidebar/module registry
            // versions.  Keeping these aliases prevents stale links on any
            // tenant from returning 404 after deployment.
            Route::middleware($middleware)->group(function (): void {
                Route::get('customerstatements', [CustomerStatementController::class, 'index'])
                    ->name('customerstatements.compat.compact');

                Route::get('customer-statements-module', [CustomerStatementController::class, 'index'])
                    ->name('customerstatements.compat.module');

                Route::get('customerstatements-module', [CustomerStatementController::class, 'index'])
                    ->name('customerstatements.compat.compact-module');
            });
        });
    }
}
