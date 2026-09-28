<?php

namespace Modules\LeadsNew\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\LeadsNew\Http\Middleware\EnsureLeadsNewEnabled;

class LeadsNewServiceProvider extends ServiceProvider
{
    /**
     * Module name.
     *
     * @var string
     */
    protected $moduleName = 'LeadsNew';

    /**
     * Module name lowercase / view and lang namespace.
     *
     * @var string
     */
    protected $moduleNameLower = 'leadsnew';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Register module middleware alias inside the module, same standalone pattern as Customers.
        try {
            $this->app['router']->aliasMiddleware('leadsnew.access', EnsureLeadsNewEnabled::class);
        } catch (\Throwable $e) {
            // Keep host ERP safe if middleware registration is unavailable during bootstrap.
        }

        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();

        $migrationPath = module_path($this->moduleName, 'Database/Migrations');
        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Register routes the same way as the working Customers module.
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $configPath = module_path($this->moduleName, 'Config/config.php');

        if (file_exists($configPath)) {
            $this->publishes([
                $configPath => config_path($this->moduleNameLower . '.php'),
            ], 'config');

            $this->mergeConfigFrom($configPath, $this->moduleNameLower);
        }

        $permissionsPath = module_path($this->moduleName, 'Config/permissions.php');
        if (file_exists($permissionsPath)) {
            $this->mergeConfigFrom($permissionsPath, $this->moduleNameLower . '.permissions');
        }
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower . '-module-views']);

        $paths = array_values(array_filter(array_merge(array_map(function ($path) {
            return $path . '/modules/' . $this->moduleNameLower;
        }, config('view.paths', [])), [$sourcePath]), 'is_dir'));

        $this->loadViewsFrom($paths, $this->moduleNameLower);

        // Backward-compatible alias for any old cached/published views.
        $this->loadViewsFrom($paths, 'leads_new');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'Resources/lang');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        }

        if (is_dir($sourcePath)) {
            $this->loadTranslationsFrom($sourcePath, $this->moduleNameLower);
        }

        // Register a secondary namespace for older pages that may have been
        // cached or published with the underscore naming convention.
        if (is_dir($sourcePath)) {
            $this->loadTranslationsFrom($sourcePath, 'leads_new');
        }
    }

    /**
     * Register factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        $factoryPath = module_path($this->moduleName, 'Database/factories');
        if (! app()->environment('production') && is_dir($factoryPath)) {
            app(Factory::class)->load($factoryPath);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
