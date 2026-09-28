<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Finance\Http\Middleware\InitializeFinanceTenantContext;

/**
 * Final route owner for Finance > List Accounts.
 *
 * The Finance module already registers /finance/account during its normal
 * provider boot.  This provider is intentionally registered from
 * AppServiceProvider and then waits until the whole application has booted
 * before loading the standalone route file. Registering last makes the
 * standalone controller authoritative while the Finance context middleware
 * preserves both central-database and Stancl-tenant businesses.
 */
class FinanceStandaloneAccountRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // A built route cache already contains the final route collection.
        // During route:cache construction this is false, so these routes are
        // still included in the generated cache.
        if ($this->app->routesAreCached()) {
            return;
        }

        // Do NOT return merely because finance.account.index already exists.
        // The normal Finance module deliberately registers that name first.
        // We must register the standalone routes AFTER every provider has
        // booted so /finance/account resolves to StandaloneAccountController.
        $this->app->booted(function (): void {
            $this->registerStandaloneFinanceAccountRoutes();
        });
    }

    protected function registerStandaloneFinanceAccountRoutes(): void
    {
        $modulePath = base_path('Modules/Finance');
        $routeFile = $modulePath . '/Routes/standalone_accounts.php';
        $controllerFile = $modulePath . '/Http/Controllers/StandaloneAccountController.php';
        $viewPath = $modulePath . '/Resources/views';

        if (! is_file($routeFile) || ! is_file($controllerFile)) {
            Log::error('Finance standalone List Accounts bootstrap files are missing.', [
                'route_file' => $routeFile,
                'controller_file' => $controllerFile,
            ]);
            return;
        }

        $controllerClass = \Modules\Finance\Http\Controllers\StandaloneAccountController::class;
        if (! class_exists($controllerClass, false)) {
            require_once $controllerFile;
        }

        if (! class_exists($controllerClass, false)) {
            Log::error('Finance standalone List Accounts controller could not be loaded.', [
                'controller_file' => $controllerFile,
            ]);
            return;
        }

        if (is_dir($viewPath)) {
            View::addNamespace('finance', $viewPath);
        }

        Route::middleware([
            'web',
            InitializeFinanceTenantContext::class,
            'IsInstalled',
            'auth:customer,web',
            'SetSessionData',
            'DayEnd',
            'language',
            'timezone',
            'bootstrap',
            'isVerified',
        ])->group($routeFile);
    }
}
