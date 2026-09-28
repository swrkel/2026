<?php

namespace Modules\Customers\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Path to home.
     *
     * @var string
     */
    public const HOME = '/customers';

    /**
     * Define route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Define the routes for the module.
     *
     * @return void
     */
    public function map()
    {
        $this->mapWebRoutes();
        $this->mapPortalRoutes();
        $this->mapApiRoutes();
    }

    /**
     * Define web routes.
     *
     * @return void
     */
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
        Route::middleware([
                'web',
                'tenant.context',
                'auth',
                'SetSessionData',
                'language',
                'timezone',
            ])
            ->namespace('Modules\Customers\Http\Controllers')
            ->group(module_path('Customers', '/Routes/web.php'));
    }

    /**
     * Distribution Dealer / Customer self-service portal routes.
     * These routes are intentionally not protected by ERP auth because customers
     * log in using their own 4 digit customer passcode.
     */
    protected function mapPortalRoutes()
    {
        Route::middleware([
                'web',
                'SetSessionData',
                'language',
                'timezone',
                'tenant.context'
            ])
            ->namespace('Modules\Customers\Http\Controllers')
            ->group(module_path('Customers', '/Routes/portal.php'));
    }


    /**
     * Distribution Dealer API routes.
     * These routes are intentionally isolated from ERP auth and ERP sidebars.
     * API authentication is handled by the Customers module dealer token middleware.
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api/dealer')
            ->middleware([
                'api',
                'tenant.context'
            ])
            ->namespace('Modules\Customers\Http\Controllers\Api')
            ->group(module_path('Customers', '/Routes/api.php'));
    }

}
