<?php

namespace Modules\PetroPD\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\Config;
use Modules\PetroPD\Console\Commands\BackfillWalkInCustomerLedgerPairs;
use Modules\PetroPD\Console\Commands\RepairPetroPdPaymentIntegrity;
use Modules\PetroPD\Console\Commands\VerifyPetroPdPaymentIntegrity;
use Modules\PetroPD\Console\Commands\CheckSettlementIntegrity;
use Modules\PetroPD\Console\Commands\ShiftLinkReport;

class PetroPDServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'PetroPD';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'petropd';

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
    $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

    // Add this line to load web routes
    // Routes load through this module's RouteServiceProvider.

    if ($this->app->runningInConsole()) {
        $this->commands([
            BackfillWalkInCustomerLedgerPairs::class,
            RepairPetroPdPaymentIntegrity::class,
            VerifyPetroPdPaymentIntegrity::class,
            // 22 Aug 2026: reports settlements whose records disagree with
            // each other - see PdSettlementIntegrityChecker.
            CheckSettlementIntegrity::class,
            // Stage 1 of "one place says which shift" - reports rows whose
            // copies of shift/operator disagree with their assignment.
            ShiftLinkReport::class,
        ]);
    }
}


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
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
        foreach (Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
