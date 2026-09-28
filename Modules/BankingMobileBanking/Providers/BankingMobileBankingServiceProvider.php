<?php

namespace Modules\BankingMobileBanking\Providers;

use Illuminate\Support\ServiceProvider;

class BankingMobileBankingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'bankingmobile');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/permissions.php', 'banking_mobile_banking.permissions');
    }
}
