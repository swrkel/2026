<?php

namespace Modules\ReportsOther\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\ReportsOther\Services\AmountWordsService;
use Modules\ReportsOther\Services\BusinessSettingsGateway;
use Modules\ReportsOther\Services\OrganizationGateway;
use Modules\ReportsOther\Services\ProductCatalogGateway;
use Modules\ReportsOther\Services\ReceiptService;
use Modules\ReportsOther\Services\ReceiptSourceDataGateway;
use Modules\ReportsOther\Services\ReportDeliveryService;
use Modules\ReportsOther\Services\ReportFormatter;
use Modules\ReportsOther\Services\SequenceService;
use Modules\ReportsOther\Support\CurrentScope;

class ReportsOtherServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register every module-owned manifest/config so the host application's
        // automatic module, Manage Page and Manage Side Bar discovery can see it.
        // No core or other module file is required by ReportsOther.
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'reportsother');
        $this->mergeConfigFrom(__DIR__.'/../Config/pages.php', 'reportsother.pages');
        $this->mergeConfigFrom(__DIR__.'/../Config/permissions.php', 'reportsother.permissions');
        $this->mergeConfigFrom(__DIR__.'/../Config/sidebar.php', 'reportsother.sidebar');

        $this->app->singleton(CurrentScope::class);
        $this->app->singleton(ProductCatalogGateway::class);
        $this->app->singleton(BusinessSettingsGateway::class);
        $this->app->singleton(OrganizationGateway::class);
        $this->app->singleton(AmountWordsService::class);
        $this->app->singleton(ReceiptSourceDataGateway::class);
        $this->app->singleton(ReportFormatter::class);
        $this->app->singleton(SequenceService::class);
        $this->app->singleton(ReceiptService::class);
        $this->app->singleton(ReportDeliveryService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'reportsother');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'reportsother');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('reportsother.php'),
        ], 'reportsother-config');
    }
}
