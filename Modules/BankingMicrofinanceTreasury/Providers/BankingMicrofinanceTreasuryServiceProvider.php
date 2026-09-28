<?php

namespace Modules\BankingMicrofinanceTreasury\Providers;

use Illuminate\Support\ServiceProvider;

class BankingMicrofinanceTreasuryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'bankingmicrofinancetreasury');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'bankingmicrofinancetreasury');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'bankingmicrofinancetreasury');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
