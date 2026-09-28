<?php

namespace Modules\BankingRisk\Providers;

use Illuminate\Support\ServiceProvider;

class BankingRiskServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'bankingrisk');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'bankingrisk');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
