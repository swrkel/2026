<?php

namespace Modules\DigitalWallet\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\DigitalWallet\Services\Ledger\DigitalWalletLedgerService;
use Modules\DigitalWallet\Services\Reports\DigitalWalletReportService;
use Modules\DigitalWallet\Services\Rules\DigitalWalletRuleService;
use Modules\DigitalWallet\Services\Support\DigitalWalletSidebarRegistrar;
use Modules\DigitalWallet\Services\Wallets\DigitalWalletService;

class DigitalWalletServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'DigitalWallet';
    protected string $moduleNameLower = 'digitalwallet';

    public function boot(): void
    {
        $this->loadViewsFrom(module_path($this->moduleName, 'Resources/views'), $this->moduleNameLower);
        $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        $this->registerSidebarMenu();

        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower);
        $this->mergeConfigFrom(module_path($this->moduleName, 'Config/menu.php'), $this->moduleNameLower . '_menu');
        $this->mergeConfigFrom(module_path($this->moduleName, 'Config/permissions.php'), $this->moduleNameLower . '_permissions');

        $this->app->singleton(DigitalWalletLedgerService::class, fn () => new DigitalWalletLedgerService());
        $this->app->singleton(DigitalWalletRuleService::class, fn () => new DigitalWalletRuleService());
        $this->app->singleton(DigitalWalletReportService::class, fn () => new DigitalWalletReportService());
        $this->app->singleton(DigitalWalletService::class, fn ($app) => new DigitalWalletService($app->make(DigitalWalletLedgerService::class), $app->make(DigitalWalletRuleService::class)));
    }

    protected function registerSidebarMenu(): void
    {
        Blade::include($this->moduleNameLower . '::partials.sidebar', 'digitalWalletSidebar');

        $menu = DigitalWalletSidebarRegistrar::menu();
        $partial = DigitalWalletSidebarRegistrar::sidebarPartial();

        View::share('digitalWalletMenu', $menu);
        View::share('digitalwalletMenu', $menu);
        View::share('digitalWalletSidebarPartial', $partial);

        View::composer('*', function ($view) use ($menu, $partial) {
            $moduleMenus = $view->offsetExists('module_menus') ? (array) $view->offsetGet('module_menus') : [];
            $moduleMenus['digitalwallet'] = $menu;

            $partials = $view->offsetExists('module_sidebar_partials') ? (array) $view->offsetGet('module_sidebar_partials') : [];
            $partials['digitalwallet'] = $partial;

            $view->with('digitalWalletMenu', $menu);
            $view->with('digitalwalletMenu', $menu);
            $view->with('digitalWalletSidebarPartial', $partial);
            $view->with('module_menus', $moduleMenus);
            $view->with('module_sidebar_partials', $partials);
        });
    }
}
