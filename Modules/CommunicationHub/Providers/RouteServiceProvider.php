<?php

namespace Modules\CommunicationHub\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\CommunicationHub\Http\Middleware\InitializeCommunicationHubTenant;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'CommunicationHub';

    /**
     * Laravel 10 still calls map() as a compatibility fallback. Keeping all route
     * loading here also supports older host ERP builds using the same module.
     */
    public function map(): void
    {
        $this->mapWebRoutes();
        $this->mapTenantRoutes();
        $this->mapApiRoutes();
    }

    protected function mapWebRoutes(): void
    {
        $route = module_path($this->moduleName, 'Routes/web.php');

        if (file_exists($route)) {
            Route::middleware('web')->group($route);
        }
    }

    protected function mapTenantRoutes(): void
    {
        $route = module_path($this->moduleName, 'Routes/tenant.php');

        if (file_exists($route)) {
            Route::middleware([
                'web',
                'auth',
                InitializeCommunicationHubTenant::class,
            ])->group($route);
        }
    }

    protected function mapApiRoutes(): void
    {
        $apiRoute = module_path($this->moduleName, 'Routes/api.php');

        if (file_exists($apiRoute)) {
            Route::prefix('api')
                ->middleware(['api', InitializeCommunicationHubTenant::class])
                ->group($apiRoute);
        }
    }
}
