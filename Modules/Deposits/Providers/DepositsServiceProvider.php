<?php

namespace Modules\Deposits\Providers;

use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\ServiceProvider;

class DepositsServiceProvider extends ServiceProvider
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
            __DIR__ . '/../Config/config.php' => config_path('deposits.php'),
        ], 'config');
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'deposits');
    }

    public function registerViews()
    {
        $viewPath = resource_path('views/modules/deposits');
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([$sourcePath => $viewPath], 'views');

        $viewPaths = array_values(array_filter(
            array_merge(array_map(function ($path) {
            return $path . '/modules/deposits';
        }, config('view.paths')), [$sourcePath]),
            'is_dir'
        ));

        $this->loadViewsFrom($viewPaths, 'deposits');
    }

    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/deposits');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'deposits');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'deposits');
        }
    }

    public function registerFactories()
    {
        if (! app()->environment('production') && $this->app->runningInConsole() && class_exists(Factory::class)) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }
}
