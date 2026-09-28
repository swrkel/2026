<?php

namespace Modules\PumperDashboardNew\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\PumperDashboardNew\Console\Commands\RetryPoneIntegration;
use Modules\PumperDashboardNew\Http\Middleware\EnsurePoneOperatorSession;
use Modules\PumperDashboardNew\Http\Middleware\EnsurePoneSchema;
use Modules\PumperDashboardNew\Http\Middleware\InitializePoneTenantContext;
use Modules\PumperDashboardNew\Services\PoneSchemaService;

class PumperDashboardNewServiceProvider extends ServiceProvider
{
    /** @var string */
    protected $moduleName = 'PumperDashboardNew';

    /** @var string */
    protected $moduleNameLower = 'pumperdashboardnew';

    public function register(): void
    {
        $root = base_path('Modules/PumperDashboardNew');
        $config = $root . '/Config/config.php';

        if (is_file($config)) {
            $this->mergeConfigFrom($config, $this->moduleNameLower);
        }

        $this->app->singleton(PoneSchemaService::class);

        if ($this->app->runningInConsole()) {
            $this->commands([RetryPoneIntegration::class]);
        }
    }

    public function boot(): void
    {
        $root = base_path('Modules/PumperDashboardNew');

        $this->loadViewsFrom($root . '/Resources/views', $this->moduleNameLower);
        $this->loadTranslationsFrom($root . '/Resources/lang', $this->moduleNameLower);
        $this->loadMigrationsFrom($root . '/Database/Migrations');

        $this->publishes([
            $root . '/Resources/assets' => public_path('modules/pumperdashboardnew'),
        ], 'pumperdashboardnew-public');

        $this->registerModuleRoutes($root);
    }

    /**
     * Register the module routes directly from the primary module provider.
     *
     * Some long-running deployments keep an old provider/module discovery
     * manifest. Registering from the provider that Nwidart always loads avoids
     * a missing RouteServiceProvider causing every module URL to return 404.
     */
    private function registerModuleRoutes(string $root): void
    {
        // Do not return merely because an old route cache exists. File-manager
        // deployments can retain a cache generated before this module was added;
        // the route-name guards below safely add only routes that are missing.

        $prefix = trim((string) config('pumperdashboardnew.route_prefix', 'pumper-dashboard-new'), '/ ');
        $name = trim((string) config('pumperdashboardnew.route_name', 'pumper-dashboard-new.'), '. ') . '.';

        $publicRoutes = $root . '/Routes/public.php';
        if (!Route::has($name . 'login') && is_file($publicRoutes)) {
            Route::middleware([
                'web',
                InitializePoneTenantContext::class,
            ])
                ->prefix($prefix)
                ->name($name)
                ->group($publicRoutes);
        }

        $operatorRoutes = $root . '/Routes/operator.php';
        if (!Route::has($name . 'operator.dashboard') && is_file($operatorRoutes)) {
            Route::middleware([
                'web',
                InitializePoneTenantContext::class,
                EnsurePoneSchema::class,
                'auth',
                EnsurePoneOperatorSession::class,
            ])
                ->prefix($prefix)
                ->name($name)
                ->group($operatorRoutes);
        }

        $adminRoutes = $root . '/Routes/web.php';
        if (!Route::has($name . 'admin.dashboard') && is_file($adminRoutes)) {
            Route::middleware([
                'web',
                InitializePoneTenantContext::class,
                EnsurePoneSchema::class,
                'auth',
            ])
                ->prefix($prefix)
                ->name($name)
                ->group($adminRoutes);
        }
    }
}
