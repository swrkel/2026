<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Late, safe bootstrap for the standalone Suppliers module.
 *
 * Only this tiny provider is registered from AppServiceProvider::register().
 * The full Suppliers provider is registered after Laravel is completely booted,
 * so framework services such as cache/session/translator are already available.
 */
class SuppliersStandaloneRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $this->app->booted(function (): void {
            $this->bootSuppliersProviderSafely();
            $this->registerStandaloneSuppliersRoutes();
        });
    }

    protected function bootSuppliersProviderSafely(): void
    {
        $providerClass = \Modules\Suppliers\Providers\SuppliersServiceProvider::class;
        $providerFile = base_path('Modules/Suppliers/Providers/SuppliersServiceProvider.php');

        if (! is_file($providerFile)) {
            return;
        }

        if (! class_exists($providerClass, false)) {
            require_once $providerFile;
        }

        if (class_exists($providerClass, false) && ! $this->app->getProvider($providerClass)) {
            // Application is fully booted at this point. Laravel will therefore
            // register AND boot this provider immediately, without the early
            // container-resolution problem caused by AppServiceProvider::register().
            $this->app->register($providerClass);
        }
    }

    protected function registerStandaloneSuppliersRoutes(): void
    {
        if (Route::has('suppliers.records.index')) {
            return;
        }

        $modulePath = base_path('Modules/Suppliers');
        $routeFile = $modulePath . '/Routes/web.php';
        $viewPath = $modulePath . '/Resources/views';
        $langPath = $modulePath . '/Resources/lang';

        if (! is_file($routeFile)) {
            Log::error('Standalone Suppliers route bootstrap file is missing.', [
                'route_file' => $routeFile,
            ]);
            return;
        }

        if (is_dir($viewPath)) {
            View::addNamespace('suppliers', $viewPath);
        }

        if (is_dir($langPath)) {
            $this->app->make('translator')->addNamespace('suppliers', $langPath);
        }

        require $routeFile;

        if (! Route::has('suppliers.records.index')) {
            Log::error('Standalone Suppliers routes were loaded but the canonical route is still missing.', [
                'route_file' => $routeFile,
            ]);
        }
    }
}
