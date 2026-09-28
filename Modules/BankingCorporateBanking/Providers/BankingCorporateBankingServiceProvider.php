<?php

namespace Modules\BankingCorporateBanking\Providers;

use Illuminate\Support\ServiceProvider;

class BankingCorporateBankingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modulePath = dirname(__DIR__);
        $this->loadRoutesFrom($modulePath . '/Routes/web.php');
        $this->loadViewsFrom($modulePath . '/Resources/views', 'bankingcorporatebanking');
        $this->loadMigrationsFrom($modulePath . '/Database/Migrations');
    }

    public function register(): void
    {
        $this->app->singleton('banking-corporate-navigation', function () {
            return new \Modules\BankingCorporateBanking\Services\CorporateNavigationService();
        });
    }
}
