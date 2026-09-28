<?php

namespace Modules\BankingMicrofinance\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class BankingMicrofinanceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'bankingmicrofinance');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'bankingmicrofinance');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        Route::middleware(['web', 'auth'])
            ->prefix(config('bankingmicrofinance.route_prefix', 'banking/microfinance'))
            ->name('banking.microfinance.')
            ->group(__DIR__ . '/../Routes/web.php');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'bankingmicrofinance');
    }
}
