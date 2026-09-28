<?php

namespace Modules\Purchase\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Middleware\InitializePurchaseTenantContext;

/**
 * Compatibility route provider.
 *
 * module.json registers PurchaseServiceProvider as the authoritative loader.
 * Keep this provider aligned in case an installation explicitly registers it,
 * but do not register it again from PurchaseServiceProvider.
 */
class RouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        parent::register();

        $aliases = [
            'App\\Services\\SupplierPaymentReferenceService' => \Modules\Purchase\Services\Entry\SupplierPaymentReferenceService::class,
            'App\\Services\\StoreStockIntegrityService' => \Modules\Purchase\Services\Entry\StoreStockIntegrityService::class,
        ];

        foreach ($aliases as $legacyClass => $moduleClass) {
            if (! class_exists($legacyClass) && class_exists($moduleClass)) {
                class_alias($moduleClass, $legacyClass);
            }
        }
    }

    public function boot(): void
    {
        $middleware = [
            'web',
            'auth',
            'SetSessionData',
            'language',
            'timezone',
            InitializePurchaseTenantContext::class,
        ];

        $this->routes(function () use ($middleware): void {
            Route::middleware($middleware)
                ->prefix('purchase-module-live')
                ->as('purchase.')
                ->group(module_path('Purchase', 'Routes/web.php'));
        });
    }
}
