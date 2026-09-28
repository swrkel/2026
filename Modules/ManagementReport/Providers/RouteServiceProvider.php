<?php

namespace Modules\ManagementReport\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\ManagementReport\Http\Middleware\ActivateManagementReportTenant;
use Modules\ManagementReport\Http\Middleware\InitializeManagementReportTenant;

/**
 * Compatibility route provider.
 *
 * ManagementReportServiceProvider also registers these routes so deployments
 * that only load the first module provider remain functional. The route-name
 * guards below make loading both providers safe.
 */
class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->routes(function (): void {
            $this->registerModuleRoutes();
        });
    }

    private function registerModuleRoutes(): void
    {
        $modulePath = base_path('Modules/ManagementReport');

        $webRoutes = $modulePath . '/Routes/web.php';
        if (!Route::has('managementreport.dashboard') && is_file($webRoutes)) {
            Route::middleware([
                'web',
                'auth',
                InitializeManagementReportTenant::class,
                ActivateManagementReportTenant::class,
            ])->group($webRoutes);
        }

        $publicRoutes = $modulePath . '/Routes/public.php';
        if (!Route::has('managementreport.public.show') && is_file($publicRoutes)) {
            Route::middleware([
                'web',
                InitializeManagementReportTenant::class,
                ActivateManagementReportTenant::class,
                'throttle:120,1',
            ])->group($publicRoutes);
        }
    }
}
