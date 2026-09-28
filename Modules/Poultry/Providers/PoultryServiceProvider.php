<?php

namespace Modules\Poultry\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Poultry\Services\BatchService;
use Modules\Poultry\Services\CostingService;
use Modules\Poultry\Services\EggProductionService;
use Modules\Poultry\Services\FeedService;
use Modules\Poultry\Services\HatcheryService;
use Modules\Poultry\Services\HealthService;
use Modules\Poultry\Services\LedgerGateway;
use Modules\Poultry\Services\PerformanceCalculator;
use Modules\Poultry\Services\StockGateway;
use Modules\Poultry\Services\WithdrawalGuard;

class PoultryServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);

        /*
         * The two gateways are singletons because they are the module's only
         * seam onto core. Binding them here means a deployment that wants
         * different integration behaviour swaps one binding rather than
         * editing call sites throughout the module.
         */
        $this->app->singleton(StockGateway::class);
        $this->app->singleton(LedgerGateway::class);
        $this->app->singleton(WithdrawalGuard::class);
        $this->app->singleton(PerformanceCalculator::class);

        $this->app->bind(BatchService::class);
        $this->app->bind(FeedService::class);
        $this->app->bind(EggProductionService::class);
        $this->app->bind(HealthService::class);
        $this->app->bind(HatcheryService::class);
        $this->app->bind(CostingService::class);
    }

    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('poultry.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'poultry');
    }

    public function registerViews()
    {
        $viewPath   = resource_path('views/modules/poultry');
        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([$sourcePath => $viewPath], 'views');

        $viewPaths = array_values(array_filter(
            array_merge(
                array_map(function ($path) {
                    return $path.'/modules/poultry';
                }, config('view.paths')),
                [$sourcePath]
            ),
            'is_dir'
        ));

        $this->loadViewsFrom($viewPaths, 'poultry');
    }

    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/poultry');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'poultry');
        } else {
            $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'poultry');
        }
    }

    public function provides()
    {
        return [];
    }
}
