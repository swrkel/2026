<?php

namespace Modules\BankingInsurance\Providers;

use Illuminate\Support\ServiceProvider;

class BankingInsuranceServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'bankinginsurance');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'bankinginsurance');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->publishes([
            __DIR__ . '/../Resources/assets' => public_path('modules/bankinginsurance'),
        ], 'bankinginsurance-assets');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'bankinginsurance');
    }
}
