<?php

namespace Modules\StockTakingNew\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\StockTakingNew\Console\Commands\RunScheduledStockTakes;
use Modules\StockTakingNew\Services\StockTakingSchemaService;
use Modules\StockTakingNew\Support\RouteRegistrar;

class StockTakingNewServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'StockTakingNew';
    protected string $moduleNameLower = 'stocktakingnew';

    public function register(): void
    {
        $configPath = dirname(__DIR__) . '/Config/config.php';
        if (is_file($configPath)) {
            $this->mergeConfigFrom($configPath, $this->moduleNameLower);
        }

        $this->app->singleton(StockTakingSchemaService::class);
    }

    public function boot(): void
    {
        $moduleRoot = dirname(__DIR__);

        $viewPath = $moduleRoot . '/Resources/views';
        if (is_dir($viewPath)) {
            $this->loadViewsFrom($viewPath, $this->moduleNameLower);
        }

        $translationPath = $moduleRoot . '/Resources/lang';
        if (is_dir($translationPath)) {
            $this->loadTranslationsFrom($translationPath, $this->moduleNameLower);
        }

        $migrationPath = $moduleRoot . '/Database/Migrations';
        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([RunScheduledStockTakes::class]);
        }

        $assetPath = $moduleRoot . '/Resources/assets';
        if (is_dir($assetPath)) {
            $this->publishes([
                $assetPath => public_path('modules/stocktakingnew'),
            ], 'stocktakingnew-public');
        }

        RouteRegistrar::register($this->app);
    }
}
