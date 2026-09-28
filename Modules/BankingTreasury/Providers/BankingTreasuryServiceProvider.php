<?php

namespace Modules\BankingTreasury\Providers;

use Illuminate\Support\ServiceProvider;

class BankingTreasuryServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'BankingTreasury';
    protected string $moduleNameLower = 'bankingtreasury';

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
