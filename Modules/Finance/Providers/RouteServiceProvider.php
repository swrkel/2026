<?php

namespace Modules\Finance\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Middleware\InitializeFinanceTenantContext;

/**
 * Compatibility route provider.
 *
 * module.json currently registers FinanceServiceProvider as the single loader.
 * Keep this provider aligned so a future module activator/configuration change
 * cannot reintroduce central-domain 404 responses.
 */
class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->routes(function (): void {
            Route::middleware([InitializeFinanceTenantContext::class])
                ->group(function (): void {
                    require __DIR__ . '/../Routes/web.php';
                });
        });
    }
}
