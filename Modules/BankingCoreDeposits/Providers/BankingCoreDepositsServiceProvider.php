<?php

namespace Modules\BankingCoreDeposits\Providers;

use Illuminate\Support\ServiceProvider;

class BankingCoreDepositsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'BankingCoreDeposits';
    protected string $moduleNameLower = 'bankingcoredeposits';

    public function boot(): void
    {
        $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        $this->loadViewsFrom(module_path($this->moduleName, 'Resources/views'), $this->moduleNameLower);
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
