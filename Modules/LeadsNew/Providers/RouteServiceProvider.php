<?php

namespace Modules\LeadsNew\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Path to home.
     *
     * @var string
     */
    public const HOME = '/leads-new';

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
        $this->mapApiRoutes();
    }

    /**
     * Define web routes.
     * Keep this aligned with the working Customers module route pattern so
     * the ERP tenant context is initialized before Leads-New controllers run.
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
            ->group(module_path('LeadsNew', '/Routes/web.php'));
    }

    /**
     * Define API routes.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        $apiRoutes = module_path('LeadsNew', '/Routes/api.php');

        if (file_exists($apiRoutes)) {
            Route::middleware([
                    'api',
                    'tenant.context',
                ])
                ->group($apiRoutes);
        }
    }
}
