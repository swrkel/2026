<?php

namespace Modules\BankingTradeFinance\Providers;

use Illuminate\Support\ServiceProvider;

class BankingTradeFinanceServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'bankingtradefinance');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'bankingtradefinance');
    }
}
