<?php

namespace Modules\Pawning\Providers;

use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\ServiceProvider;

class PawningServiceProvider extends ServiceProvider
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
            __DIR__ . '/../Config/config.php' => config_path('pawning.php'),
        ], 'config');
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'pawning');
    }

    protected function registerViews()
    {
        $viewPath = resource_path('views/modules/pawning');
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([$sourcePath => $viewPath], 'views');

        $viewPaths = array_values(array_filter(
            array_merge(array_map(function ($path) {
            return $path . '/modules/pawning';
        }, config('view.paths')), [$sourcePath]),
            'is_dir'
        ));

        $this->loadViewsFrom($viewPaths, 'pawning');
    }

    protected function registerTranslations()
    {
        $langPath = resource_path('lang/modules/pawning');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'pawning');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'pawning');
        }
    }

    protected function registerFactories()
    {
        if (! app()->environment('production') && $this->app->runningInConsole() && class_exists(Factory::class)) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }
}
