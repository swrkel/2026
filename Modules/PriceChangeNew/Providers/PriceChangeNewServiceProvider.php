<?php

namespace Modules\PriceChangeNew\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\PriceChangeNew\Console\Commands\ApplyDuePriceChanges;

class PriceChangeNewServiceProvider extends ServiceProvider
{
    protected $moduleNameLower = 'pricechangenew';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', $this->moduleNameLower);
        $this->mergeConfigFrom(__DIR__ . '/../Config/permissions.php', $this->moduleNameLower . '.permissions');

        if (! $this->app->getProvider(RouteServiceProvider::class)) {
            $this->app->register(RouteServiceProvider::class);
        }
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', $this->moduleNameLower);
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', $this->moduleNameLower);

        if (is_dir(__DIR__ . '/../Database/Migrations')) {
            $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([ApplyDuePriceChanges::class]);
        }

        $this->publishes([
            __DIR__ . '/../public' => public_path('modules/pricechangenew'),
        ], 'pricechangenew-assets');
    }
}
