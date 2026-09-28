<?php

namespace Modules\StockTakingNew\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Modules\StockTakingNew\Http\Middleware\EnsureStockTakingSchema;
use Modules\StockTakingNew\Http\Middleware\InitializeStockTakingTenantContext;

/**
 * Registers Stock Taking - New routes without depending on a fresh Laravel
 * route cache or a freshly rebuilt nwidart module-provider manifest.
 */
final class RouteRegistrar
{
    public static function register(Application $app): void
    {
        // Route::has() is authoritative. Do not trust an old container marker:
        // an earlier provider may have set it before route loading failed.
        if (Route::has('stock-taking-new.dashboard')) {
            $app->instance('stocktakingnew.routes_loaded', true);

            return;
        }

        $moduleRoot = dirname(__DIR__);
        $webRoutes = $moduleRoot . '/Routes/web.php';
        $publicRoutes = $moduleRoot . '/Routes/public.php';

        if (! is_file($webRoutes)) {
            return;
        }

        $prefix = trim((string) config('stocktakingnew.route_prefix', 'stock-taking-new'), '/ ');
        $prefix = $prefix !== '' ? $prefix : 'stock-taking-new';

        $routeName = trim((string) config('stocktakingnew.route_name', 'stock-taking-new.'), '. ');
        $routeName = ($routeName !== '' ? $routeName : 'stock-taking-new') . '.';

        // Register directly even when a stale route cache exists. This is
        // intentional for this transferred multi-domain application: cached
        // routes can pre-date a newly uploaded standalone module.
        Route::middleware([
            'web',
            InitializeStockTakingTenantContext::class,
            'auth',
            EnsureStockTakingSchema::class,
        ])
            ->prefix($prefix)
            ->name($routeName)
            ->group($webRoutes);

        if (is_file($publicRoutes) && ! Route::has('stock-taking-new.public.shared.show')) {
            Route::middleware([
                'web',
                InitializeStockTakingTenantContext::class,
            ])
                ->prefix($prefix)
                ->name('stock-taking-new.public.')
                ->group($publicRoutes);
        }

        $app->instance('stocktakingnew.routes_loaded', true);
    }
}
