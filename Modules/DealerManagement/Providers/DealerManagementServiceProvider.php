<?php

namespace Modules\DealerManagement\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\DealerManagement\Console\Commands\SyncDistributionCommand;
use Modules\DealerManagement\Console\Commands\RefreshDealerHubCommand;
use Modules\DealerManagement\Services\DealerContext;
use Modules\DealerManagement\Services\HubContext;

class DealerManagementServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'DealerManagement';
    protected string $moduleNameLower = 'dealermanagement';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'dealermanagement');
        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'dealermanagement_menu');
        $this->mergeConfigFrom(__DIR__.'/../Config/permissions.php', 'dealermanagement_permissions');
        $this->app->singleton(DealerContext::class, fn() => new DealerContext());
        $this->app->singleton(HubContext::class, fn() => new HubContext());
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'dealermanagement');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'dealermanagement');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');


        $this->publishes([
            __DIR__.'/../Resources/css' => public_path('modules/dealermanagement/css'),
            __DIR__.'/../Resources/js' => public_path('modules/dealermanagement/js'),
        ], 'dealermanagement-public');

        if ($this->app->runningInConsole()) {
            $this->commands([SyncDistributionCommand::class, RefreshDealerHubCommand::class]);
        }
    }
}
