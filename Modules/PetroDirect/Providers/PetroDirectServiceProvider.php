<?php

namespace Modules\PetroDirect\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\PetroDirect\Http\Middleware\InitializePetroDirectTenantContext;

class PetroDirectServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'PetroDirect';
    protected string $moduleNameLower = 'petrodirect';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerViews();

        $this->app->booted(function (): void {
            if ($this->app->bound('petrodirect.routes.loaded.after.application')) {
                return;
            }

            $this->app->instance('petrodirect.routes.loaded.after.application', true);
            $routePath = __DIR__ . '/../Routes/web.php';

            if (! is_file($routePath)) {
                return;
            }

            $middleware = [
                'web',
                'auth',
                'SetSessionData',
                'language',
                'timezone',
                InitializePetroDirectTenantContext::class,
            ];

            Route::middleware($middleware)
                ->prefix('petrodirect-live')
                ->as('petrodirect.')
                ->group($routePath);

            Route::middleware($middleware)
                ->prefix('petrodirect')
                ->as('petrodirect.compat.')
                ->group($routePath);

            Route::middleware($middleware)
                ->prefix('petro-direct')
                ->as('petrodirect.dashed-compat.')
                ->group($routePath);

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
        $configPath = __DIR__ . '/../Config/config.php';

        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, $this->moduleNameLower);
        }
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower . '-module-views']);

        $viewPaths = array_values(array_filter(
            array_merge(array_map(function ($path) {
                return $path . '/modules/' . $this->moduleNameLower;
            }, config('view.paths')), [$sourcePath]),
            'is_dir'
        ));

        $this->loadViewsFrom($viewPaths, $this->moduleNameLower);
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', $this->moduleNameLower);
        }
    }
}
