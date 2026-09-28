<?php

namespace Modules\StockAdjustmentNew\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\StockAdjustmentNew\Http\Middleware\EnsureStockAdjustmentSchema;
use Modules\StockAdjustmentNew\Http\Middleware\InitializeStockAdjustmentTenantContext;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->routes(function (): void {
            if ($this->app->bound('stockadjustmentnew.routes_loaded')
                || Route::has('stock-adjustment-new.dashboard')) {
                return;
            }

            $this->app->instance('stockadjustmentnew.routes_loaded', true);

            Route::middleware([
                    'web',
                    InitializeStockAdjustmentTenantContext::class,
                    'auth',
                    EnsureStockAdjustmentSchema::class,
                ])
                ->prefix(config('stockadjustmentnew.route_prefix', 'stock-adjustment-new'))
                ->name('stock-adjustment-new.')
                ->group(module_path('StockAdjustmentNew', 'Routes/web.php'));
        });
    }
}
