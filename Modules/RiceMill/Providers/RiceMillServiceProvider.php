<?php
namespace Modules\RiceMill\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Modules\RiceMill\Http\Middleware\EnsureRiceMillPermission;
use Modules\RiceMill\Http\Middleware\EnsureRiceMillContext;
use Modules\RiceMill\Services\SidebarRegistrationService;
use Modules\RiceMill\Services\BusinessPrecisionService;
use Modules\RiceMill\Services\StandardListService;
use Modules\RiceMill\Services\TenantContext;

class RiceMillServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'ricemill');
        $this->mergeConfigFrom(__DIR__.'/../Config/sidebar.php', 'ricemill.sidebar');
        $this->mergeConfigFrom(__DIR__.'/../Config/permissions.php', 'ricemill.permissions');

        // One precision resolver per request. Rice Mill renders many nested Blade
        // partials (and AJAX partials); sharing this instance prevents repeated
        // Business Settings/schema queries while keeping the tenant/business
        // values request-local.
        $this->app->singleton(BusinessPrecisionService::class);
        $this->app->singleton(StandardListService::class);
    }

    public function boot(): void
    {
        // Register both the canonical namespace used by module pages and the
        // lowercase alias used by the host's automatic module sidebar discovery.
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'RiceMill');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'ricemill');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'RiceMill');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'ricemill');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        if (method_exists($this->app['router'], 'aliasMiddleware')) {
            $this->app['router']->aliasMiddleware('rcm.permission', EnsureRiceMillPermission::class);
            $this->app['router']->aliasMiddleware('rcm.context', EnsureRiceMillContext::class);
        }

        Blade::componentNamespace('Modules\\RiceMill\\View\\Components', 'rcm');

        // Business Settings is the single precision source for every Rice Mill
        // page, modal and AJAX-rendered partial. The closure runs only when a
        // Rice Mill view is rendered, after tenant/business middleware has run.
        View::composer(['RiceMill::*', 'ricemill::*'], function ($view) {
            $fallbackCurrency = (int) config('ricemill.currency_decimals', 4);
            $fallbackQuantity = (int) config('ricemill.quantity_decimals', 3);
            $precision = [
                'currency' => $fallbackCurrency,
                'quantity' => $fallbackQuantity,
                'currency_step' => $fallbackCurrency > 0 ? '0.' . str_repeat('0', $fallbackCurrency - 1) . '1' : '1',
                'quantity_step' => $fallbackQuantity > 0 ? '0.' . str_repeat('0', $fallbackQuantity - 1) . '1' : '1',
            ];

            try {
                $businessId = app(TenantContext::class)->businessId();
                $precision = app(BusinessPrecisionService::class)->forBusiness($businessId);
            } catch (\Throwable $e) {
                // Keep views renderable in installer/console contexts.
            }

            $view->with('rcmCurrencyPrecision', $precision['currency']);
            $view->with('rcmQuantityPrecision', $precision['quantity']);
            $view->with('rcmCurrencyStep', $precision['currency_step']);
            $view->with('rcmQuantityStep', $precision['quantity_step']);
        });

        $this->publishes([
            __DIR__.'/../Resources/assets' => public_path('modules/ricemill'),
        ], 'ricemill-assets');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\RiceMill\Console\SyncPermissions::class,
                \Modules\RiceMill\Console\InstallSidebarHook::class,
                \Modules\RiceMill\Console\OptimizePerformance::class,
            ]);
        }

        try { $this->app->make(SidebarRegistrationService::class)->register(); } catch (\Throwable $e) { /* optional host integration */ }
    }
}
