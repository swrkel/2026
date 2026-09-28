<?php

namespace Modules\Purchase\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Purchase\Http\Middleware\InitializePurchaseTenantContext;

class PurchaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $views = module_path('Purchase', 'Resources/views');
        $translations = module_path('Purchase', 'Resources/lang');
        $migrations = module_path('Purchase', 'Database/Migrations');

        if (is_dir($views)) {
            $this->loadViewsFrom($views, 'purchase');
        }

        if (is_dir($translations)) {
            $this->loadTranslationsFrom($translations, 'purchase');
        }

        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }

        /*
         * Register Purchase routes after the application has booted.
         *
         * Purchase is deployed as a plug-and-play module to servers that may
         * still have an older generated route cache. Laravel's normal
         * RouteServiceProvider callback is skipped when a route cache exists,
         * which can leave only part of a newer Purchase module reachable and
         * make individual pages/AJAX endpoints return 404.
         *
         * Loading the installed module routes here makes this exact Purchase
         * package authoritative immediately without requiring route:clear or
         * optimize:clear from a non-technical user.
         */
        $this->app->booted(function (): void {
            if ($this->app->bound('purchase.routes.loaded.after.application')) {
                return;
            }

            $this->app->instance('purchase.routes.loaded.after.application', true);

            $middleware = [
                'web',
                'auth',
                'SetSessionData',
                'language',
                'timezone',
                InitializePurchaseTenantContext::class,
            ];

            /*
             * Canonical collision-proof route family.
             *
             * All route() calls inside Purchase resolve here. Keeping this URI
             * separate from old/core Purchase routes prevents a stale cached
             * route from hijacking only some pages on one server.
             */
            Route::middleware($middleware)
                ->prefix('purchase-module-live')
                ->as('purchase.')
                ->group(module_path('Purchase', 'Routes/web.php'));

            /*
             * Backward compatibility for existing bookmarks/sidebar records.
             * These use different route-name prefixes so the canonical
             * purchase.* names above remain authoritative.
             */
            Route::middleware($middleware)
                ->prefix('purchase')
                ->as('purchase.compat.')
                ->group(module_path('Purchase', 'Routes/web.php'));

            Route::middleware($middleware)
                ->prefix('purchase-module')
                ->as('purchase.module-compat.')
                ->group(module_path('Purchase', 'Routes/web.php'));

            $routes = Route::getRoutes();
            if (method_exists($routes, 'refreshNameLookups')) {
                $routes->refreshNameLookups();
            }
            if (method_exists($routes, 'refreshActionLookups')) {
                $routes->refreshActionLookups();
            }
        });
    }

    public function register(): void
    {
        $this->registerStandaloneCompatibilityAliases();

        $permissions = module_path('Purchase', 'Config/permissions.php');
        if (is_file($permissions)) {
            $this->mergeConfigFrom($permissions, 'purchase.permissions');
        }

        // Routes are intentionally loaded from boot()->app->booted() above so
        // the installed module works even when an older Laravel route cache is
        // present. RouteServiceProvider remains as a compatibility class only.
    }
    /**
     * Keep Purchase bootable on older tenant installations where application-level
     * helper services were never deployed.  The aliases are registered before any
     * controller/service graph is resolved, so even an older cached Purchase class
     * that still type-hints the legacy App\Services class can be constructed.
     *
     * When the application already provides the service, it is left completely
     * untouched and the existing implementation remains authoritative.
     */
    private function registerStandaloneCompatibilityAliases(): void
    {
        $aliases = [
            'App\\Services\\SupplierPaymentReferenceService' => \Modules\Purchase\Services\Entry\SupplierPaymentReferenceService::class,
            'App\\Services\\StoreStockIntegrityService' => \Modules\Purchase\Services\Entry\StoreStockIntegrityService::class,
        ];

        foreach ($aliases as $legacyClass => $moduleClass) {
            if (class_exists($legacyClass)) {
                continue;
            }

            if (class_exists($moduleClass)) {
                class_alias($moduleClass, $legacyClass);
            }
        }
    }

}
