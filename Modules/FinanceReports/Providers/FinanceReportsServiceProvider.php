<?php

namespace Modules\FinanceReports\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\FinanceReports\Services\Adapters\FinanceReportsEnterpriseAdapter;
use Modules\FinanceReports\Services\Framework\EnterpriseFrameworkBridgeService;

class FinanceReportsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'financereports');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'financereports');
    }

    public function register(): void
    {
        /*
         * MA-002: register this module's ROUTES.
         *
         * The provider loaded views and translations but never registered
         * RouteServiceProvider, so none of the module's 92 routes existed and
         * every Finance Reports page returned 404.
         *
         * The route file, the controllers and the views were all present and
         * correct - nothing was ever asking Laravel to load them.
         *
         * Every other module does this in its service provider, for example
         * PetroPDServiceProvider line 56:
         *
         *     $this->app->register(RouteServiceProvider::class);
         */
        $this->app->register(RouteServiceProvider::class);

        // Read-only reporting module. No bindings that alter existing Finance behaviour.
        $this->mergeConfigFrom(__DIR__ . '/../Config/financereports_enterprise.php', 'financereports_enterprise');
        $this->mergeConfigFrom(__DIR__ . '/../Config/menu.php', 'financereports.menu');
        $this->mergeConfigFrom(__DIR__ . '/../Config/reports.php', 'financereports.reports');
        $this->app->singleton(FinanceReportsEnterpriseAdapter::class);
        $this->app->singleton(EnterpriseFrameworkBridgeService::class);
    }
}
