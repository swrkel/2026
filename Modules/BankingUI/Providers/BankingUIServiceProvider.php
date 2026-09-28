<?php

namespace Modules\BankingUI\Providers;

use Illuminate\Support\ServiceProvider;

class BankingUIServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $base = dirname(__DIR__);
        $this->loadRoutesFrom($base . '/Routes/web.php');
        $this->loadViewsFrom($base . '/Resources/views', 'bankingui');
        $this->loadMigrationsFrom($base . '/Database/Migrations');
        $this->mergeConfigFrom($base . '/Config/navigation.php', 'bankingui.navigation');
        $this->mergeConfigFrom($base . '/Config/test_manager.php', 'bankingui.test_manager');
    }

    public function register(): void
    {
        $this->app->singleton(\Modules\BankingUI\Services\BankingNavigationService::class);
        $this->app->singleton(\Modules\BankingUI\Services\BankingRouteHealthService::class);
        $this->app->singleton(\Modules\BankingUI\Services\BankingTestManagerService::class);
    }
}
