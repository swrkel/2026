<?php

namespace Modules\StockAdjustmentNew\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\StockAdjustmentNew\Services\StockAdjustmentSchemaService;

class StockAdjustmentNewServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'StockAdjustmentNew';
    protected string $moduleNameLower = 'stockadjustmentnew';

    public function register(): void
    {
        $config = module_path($this->moduleName, 'Config/config.php');
        if (is_file($config)) {
            $this->mergeConfigFrom($config, $this->moduleNameLower);
        }

        $this->app->singleton(StockAdjustmentSchemaService::class);

        if (! $this->app->getProvider(RouteServiceProvider::class)) {
            $this->app->register(RouteServiceProvider::class);
        }
    }

    public function boot(): void
    {
        $translationPath = module_path($this->moduleName, 'Resources/lang');
        if (is_dir($translationPath)) {
            $this->loadTranslationsFrom($translationPath, $this->moduleNameLower);
        }

        $viewPath = module_path($this->moduleName, 'Resources/views');
        if (is_dir($viewPath)) {
            $this->loadViewsFrom($viewPath, $this->moduleNameLower);
        }

        $migrationPath = module_path($this->moduleName, 'Database/Migrations');
        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }

        $assetPath = module_path($this->moduleName, 'Resources/assets');
        if (is_dir($assetPath)) {
            $this->publishes([
                $assetPath => public_path('modules/stockadjustmentnew'),
            ], 'public');
        }
    }
}
