<?php

namespace Modules\ManagementReport\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\ManagementReport\Console\CheckTenantDatabaseCommand;
use Modules\ManagementReport\Console\InstallTenantTablesCommand;
use Modules\ManagementReport\Http\Middleware\ActivateManagementReportTenant;
use Modules\ManagementReport\Http\Middleware\InitializeManagementReportTenant;
use Modules\ManagementReport\Services\Reports\SectionRegistry;

class ManagementReportServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'ManagementReport';
    protected string $moduleNameLower = 'managementreport';

    public function register(): void
    {
        $modulePath = base_path('Modules/ManagementReport');

        $this->mergeConfigFrom($modulePath . '/Config/config.php', $this->moduleNameLower);
        $this->mergeConfigFrom($modulePath . '/Config/menu.php', $this->moduleNameLower . '_menu');
        $this->mergeConfigFrom($modulePath . '/Config/permissions.php', $this->moduleNameLower . '_permissions');
        $this->mergeConfigFrom($modulePath . '/Config/sections.php', $this->moduleNameLower . '_sections');

        $this->app->singleton(SectionRegistry::class, static function ($app): SectionRegistry {
            return new SectionRegistry($app, config('managementreport_sections', []));
        });

        // Some installations only load the first provider from module.json.
        // Register the compatibility route provider explicitly, while Laravel's
        // provider repository prevents the same class from being registered twice.
        if (!$this->app->getProvider(RouteServiceProvider::class)) {
            $this->app->register(RouteServiceProvider::class);
        }
    }

    public function boot(): void
    {
        $modulePath = base_path('Modules/ManagementReport');

        $this->loadViewsFrom($modulePath . '/Resources/views', $this->moduleNameLower);
        $this->loadTranslationsFrom($modulePath . '/Resources/lang', $this->moduleNameLower);

        // Management Report tables belong to tenant databases and are therefore
        // not loaded automatically by the central migrate command.
        $this->publishes([
            $modulePath . '/Resources/assets' => public_path('modules/management-report'),
        ], 'managementreport-assets');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallTenantTablesCommand::class,
                CheckTenantDatabaseCommand::class,
            ]);
        }

        // Register routes from the main provider as the authoritative fallback.
        // This avoids depending on a second provider being discovered from stale
        // Nwidart/service manifests. Route-name guards prevent duplicate routes.
        $this->registerModuleRoutes($modulePath);
    }

    private function registerModuleRoutes(string $modulePath): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

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
