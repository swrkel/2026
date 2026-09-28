<?php

namespace Modules\StockTransferNew\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\StockTransferNew\Http\Middleware\EnsureStockTransferSchema;
use Modules\StockTransferNew\Http\Middleware\InitializeStockTransferTenantContext;
use Modules\StockTransferNew\Services\StockTransferSchemaService;

class StockTransferNewServiceProvider extends ServiceProvider
{
    protected string $moduleNameLower = 'stocktransfernew';

    public function register(): void
    {
        $this->app->singleton(StockTransferSchemaService::class);
    }

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->registerMigrations();
        $this->registerRoutes();
    }

    protected function registerConfig(): void
    {
        $path = __DIR__ . '/../Config/config.php';
        if (is_file($path)) {
            $this->mergeConfigFrom($path, $this->moduleNameLower);
        }
    }

    protected function registerViews(): void
    {
        $sourcePath = __DIR__ . '/../Resources/views';
        $overridePath = resource_path('views/modules/' . $this->moduleNameLower);
        $paths = array_values(array_filter([$overridePath, $sourcePath], 'is_dir'));

        if ($paths !== []) {
            $this->loadViewsFrom($paths, $this->moduleNameLower);
        }
    }

    protected function registerTranslations(): void
    {
        foreach ([
            resource_path('lang/modules/' . $this->moduleNameLower),
            __DIR__ . '/../Resources/lang',
        ] as $path) {
            if (is_dir($path)) {
                $this->loadTranslationsFrom($path, $this->moduleNameLower);
            }
        }
    }

    protected function registerMigrations(): void
    {
        $path = __DIR__ . '/../Database/Migrations';
        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    protected function registerRoutes(): void
    {
        if ($this->app->routesAreCached() || $this->app->bound('stocktransfernew.routes_loaded')) {
            return;
        }

        $this->app->instance('stocktransfernew.routes_loaded', true);

        $webMiddleware = [
            'web',
            InitializeStockTransferTenantContext::class,
            'auth:customer,web',
            EnsureStockTransferSchema::class,
            'SetSessionData',
            'language',
            'timezone',
            'bootstrap',
            'check.route.permission',
        ];

        $apiMiddleware = [
            'api',
            InitializeStockTransferTenantContext::class,
            EnsureStockTransferSchema::class,
        ];

        $routeFiles = glob(__DIR__ . '/../Routes/*.php') ?: [];
        sort($routeFiles);

        foreach ($routeFiles as $path) {
            if (basename($path) === 'api.php') {
                Route::prefix('api')->middleware($apiMiddleware)->group($path);
                continue;
            }

            Route::middleware($webMiddleware)->group($path);
        }
    }
}
