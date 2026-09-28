<?php

namespace Modules\Chequer\Providers;

use Illuminate\Support\ServiceProvider;

class ChequerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // S368: load routes directly from the module service provider too.
        // Some installs only auto-register the main module service provider from module.json;
        // if RouteServiceProvider is not booted, every Chequer page returns 404.
        // Routes load through this module's RouteServiceProvider.

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'chequer');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'chequer');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    public function register(): void
    {
    }
}
