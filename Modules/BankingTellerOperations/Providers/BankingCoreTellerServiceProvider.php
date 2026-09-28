<?php

namespace Modules\BankingTellerOperations\Providers;

use Illuminate\Support\ServiceProvider;

class BankingCoreTellerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'banking-core-teller');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'banking-core-teller');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
