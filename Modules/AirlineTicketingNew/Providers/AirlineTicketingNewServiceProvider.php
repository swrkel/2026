<?php

namespace Modules\AirlineTicketingNew\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AirlineTicketingNew\Console\Commands\AirlineTicketingCertifyCommand;
use Modules\AirlineTicketingNew\Console\Commands\AirlineTicketingDailyCommand;
use Modules\AirlineTicketingNew\Console\Commands\AirlineTicketingDeployCheckCommand;
use Modules\AirlineTicketingNew\Console\Commands\AirlineTicketingDeployCommand;
use Modules\AirlineTicketingNew\Console\Commands\AirlineTicketingEnterpriseCertifyCommand;
use Modules\AirlineTicketingNew\Console\Commands\AirlineTicketingFinalInstallCommand;
use Modules\AirlineTicketingNew\Console\Commands\AirlineTicketingHealthCommand;
use Modules\AirlineTicketingNew\Console\Commands\AirlineTicketingInstallCommand;
use Modules\AirlineTicketingNew\Console\Commands\AirlineTicketingProductionReadyCommand;
use Modules\AirlineTicketingNew\Services\Scope\AirlineTicketingScopeService;

class AirlineTicketingNewServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'AirlineTicketingNew';
    protected string $moduleNameLower = 'airlineticketingnew';

    public function boot(): void
    {
        // Consolidated command registration for all supplied ATN parcels.
        if ($this->app->runningInConsole()) {
            $this->commands([
                AirlineTicketingCertifyCommand::class,
                AirlineTicketingDailyCommand::class,
                AirlineTicketingDeployCheckCommand::class,
                AirlineTicketingDeployCommand::class,
                AirlineTicketingEnterpriseCertifyCommand::class,
                AirlineTicketingFinalInstallCommand::class,
                AirlineTicketingHealthCommand::class,
                AirlineTicketingInstallCommand::class,
                AirlineTicketingProductionReadyCommand::class,
            ]);
        }

        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(AirlineTicketingScopeService::class, function () {
            return new AirlineTicketingScopeService();
        });
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->moduleNameLower . '-module-views']);
        $this->loadViewsFrom([$sourcePath, $viewPath], $this->moduleNameLower);
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(
                module_path($this->moduleName, 'Resources/lang'),
                $this->moduleNameLower
            );
        }
    }

    public function provides(): array
    {
        return [];
    }
}
