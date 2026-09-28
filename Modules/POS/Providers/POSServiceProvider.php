<?php

namespace Modules\POS\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class POSServiceProvider extends ServiceProvider
{
    protected $moduleName = 'POS';
    protected $moduleNameLower = 'pos';

    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->registerMigrations();
        // Routes load exclusively through RouteServiceProvider.
    }

    public function register()
    {
        // Keep POS route provider registration inside POS only.
        if (! $this->app->bound('pos.route_provider_registered')) {
            $this->app->instance('pos.route_provider_registered', true);
            $this->app->register(RouteServiceProvider::class);
        }
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
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);

        $paths = [];
        foreach ((array) config('view.paths', []) as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }

        $this->loadViewsFrom(array_merge($paths, [$sourcePath, $viewPath]), $this->moduleNameLower);
    }

    protected function registerTranslations(): void
    {
        $moduleLangPath = __DIR__ . '/../Resources/lang';
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        }

        if (is_dir($moduleLangPath)) {
            $this->loadTranslationsFrom($moduleLangPath, $this->moduleNameLower);
        }
    }

    protected function registerMigrations(): void
    {
        $path = __DIR__ . '/../Database/Migrations';
        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }


}
