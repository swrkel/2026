<?php

namespace Modules\BankingCRM\Providers;

use Illuminate\Support\ServiceProvider;

class BankingCRMServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'bankingcrm');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'bankingcrm');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
