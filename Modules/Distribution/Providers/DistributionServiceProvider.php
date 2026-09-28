<?php

namespace Modules\Distribution\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\Distribution\Services\Exports\DistributionExportService;
use Modules\Distribution\Services\Invoices\DistributionInvoiceService;
use Modules\Distribution\Services\Loadings\DistributionLoadingService;
use Modules\Distribution\Services\Payments\DistributionPaymentService;
use Modules\Distribution\Services\Printing\DistributionPrintService;
use Modules\Distribution\Services\Reports\DistributionReportService;
use Modules\Distribution\Support\DistributionMenuRegistry;
use Modules\Distribution\Support\DistributionPermissionRegistry;
use Modules\Distribution\Support\DistributionRouteRegistry;

class DistributionServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'Distribution';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'distribution';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);

        // Distribution-owned service seams. Existing working controller logic is
        // preserved; these bindings provide module-local ownership for gradual
        // migration away from main ERP helpers/controllers.
        $this->app->singleton(DistributionInvoiceService::class);
        $this->app->singleton(DistributionLoadingService::class);
        $this->app->singleton(DistributionPaymentService::class);
        $this->app->singleton(DistributionPrintService::class);
        $this->app->singleton(DistributionExportService::class);
        $this->app->singleton(DistributionReportService::class);

        // Distribution-owned route, menu and permission registries.
        $this->app->singleton(DistributionRouteRegistry::class);
        $this->app->singleton(DistributionMenuRegistry::class);
        $this->app->singleton(DistributionPermissionRegistry::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        }
    }

    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        if (! app()->environment('production') && $this->app->runningInConsole()) {
            app(Factory::class)->load(module_path($this->moduleName, 'Database/factories'));
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
