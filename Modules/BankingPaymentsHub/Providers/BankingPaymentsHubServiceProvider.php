<?php

namespace Modules\BankingPaymentsHub\Providers;

use Illuminate\Support\ServiceProvider;

class BankingPaymentsHubServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'BankingPaymentsHub';
    protected string $moduleNameLower = 'bankingpaymentshub';

    public function boot(): void
    {
        $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
        $this->loadViewsFrom(module_path($this->moduleName, 'Resources/views'), $this->moduleNameLower);
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    public function register(): void
    {
        $this->mergeConfigFrom(module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower);
        $this->app->register(RouteServiceProvider::class);
    }
}
