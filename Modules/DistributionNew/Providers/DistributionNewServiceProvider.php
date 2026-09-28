<?php

namespace Modules\DistributionNew\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class DistributionNewServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'DistributionNew';
    protected string $moduleNameLower = 'distributionnew';

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', $this->moduleNameLower);
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', $this->moduleNameLower);
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->registerWebRoutes();
        $this->registerApiRoutes();

        $this->publishes([
            __DIR__ . '/../Resources/css' => public_path('modules/distributionnew/css'),
            __DIR__ . '/../Resources/js' => public_path('modules/distributionnew/js'),
        ], 'distributionnew-public');

        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('distributionnew.php'),
        ], 'distributionnew-config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', $this->moduleNameLower);
    }

    protected function registerWebRoutes(): void
    {
        $routeFile = __DIR__ . '/../Routes/web.php';
        if (file_exists($routeFile)) {
            Route::middleware(['web', 'auth'])
                ->namespace('Modules\\DistributionNew\\Http\\Controllers')
                ->group($routeFile);
        }
    }

    protected function registerApiRoutes(): void
    {
        $routeFile = __DIR__ . '/../Routes/api.php';
        if (file_exists($routeFile)) {
            Route::prefix('api/distribution-new')
                ->middleware(['api'])
                ->namespace('Modules\\DistributionNew\\Http\\Controllers')
                ->as('api.distributionnew.')
                ->group($routeFile);
        }
    }
}
