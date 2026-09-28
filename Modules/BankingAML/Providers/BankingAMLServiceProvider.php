<?php

namespace Modules\BankingAML\Providers;

use Illuminate\Support\ServiceProvider;

class BankingAMLServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'bankingaml');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'bankingaml');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
