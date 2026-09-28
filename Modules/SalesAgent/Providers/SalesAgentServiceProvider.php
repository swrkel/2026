<?php

namespace Modules\SalesAgent\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

class SalesAgentServiceProvider extends ServiceProvider
{
    protected $moduleName = 'SalesAgent';
    protected $moduleNameLower = 'salesagent';

    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();

        if (is_dir(module_path($this->moduleName, 'Database/Migrations'))) {
            $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
        }

        // Direct route boot for this standalone module.
        // This avoids route provider discovery issues and keeps routes inside the module.
        Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone', 'tenant.context'])
            ->prefix('salesagent')
            ->as('salesagent.')
            ->group(module_path($this->moduleName, 'Routes/web.php'));
    }

    public function register()
    {
        // Keep this provider standalone. Routes are loaded directly in boot().
    }

    protected function registerConfig()
    {
        $configPath = module_path($this->moduleName, 'Config/config.php');

        if (file_exists($configPath)) {
            $this->publishes([
                $configPath => config_path($this->moduleNameLower . '.php'),
            ], 'config');

            $this->mergeConfigFrom($configPath, $this->moduleNameLower);
        }
    }

    public function registerViews()
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/views');

        if (is_dir($sourcePath)) {
            $this->publishes([
                $sourcePath => $viewPath,
            ], ['views', $this->moduleNameLower . '-module-views']);

            $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
        }
    }

    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/lang');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } elseif (is_dir($sourcePath)) {
            $this->loadTranslationsFrom($sourcePath, $this->moduleNameLower);
        }
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];

        foreach (Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }

        return $paths;
    }

    public function provides()
    {
        return [];
    }
}
