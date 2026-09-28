<?php

namespace Modules\SalesAgent\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected $moduleNamespace = 'Modules\SalesAgent\Http\Controllers';

    public function boot()
    {
        parent::boot();
    }

    public function map()
    {
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes()
    {
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
        Route::middleware(['web', 'tenant.context', 'auth', 'SetSessionData', 'language', 'timezone'])
            ->namespace($this->moduleNamespace)
            ->prefix('salesagent')
            ->as('salesagent.')
            ->group(module_path('SalesAgent', 'Routes/web.php'));
    }
}
