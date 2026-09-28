<?php
namespace Modules\BankingAtmDebitCard\Providers;

use Illuminate\Support\ServiceProvider;

class BankingAtmDebitCardServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'banking-cards');
        $this->loadTranslationsFrom(__DIR__.'/../Lang', 'banking-cards');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->publishes([__DIR__.'/../Resources/assets' => public_path('modules/banking-atm-debit-card')], 'public');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'banking-atm-debit-card');
    }
}
