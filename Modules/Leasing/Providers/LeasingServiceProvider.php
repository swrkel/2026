<?php

namespace Modules\Leasing\Providers;

use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\ServiceProvider;

class LeasingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerConfig()
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('leasing.php'),
        ], 'config');
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'leasing');
    }

    protected function registerViews()
    {
        $viewPath = resource_path('views/modules/leasing');
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([$sourcePath => $viewPath], 'views');

        $viewPaths = array_values(array_filter(
            array_merge(array_map(function ($path) {
            return $path . '/modules/leasing';
        }, config('view.paths')), [$sourcePath]),
            'is_dir'
        ));

        $this->loadViewsFrom($viewPaths, 'leasing');
    }

    protected function registerTranslations()
    {
        $langPath = resource_path('lang/modules/leasing');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'leasing');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'leasing');
        }
    }

    protected function registerFactories()
    {
        if (! app()->environment('production') && $this->app->runningInConsole() && class_exists(Factory::class)) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }
}
