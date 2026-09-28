<?php

namespace Modules\Graphs\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Graphs\Http\Middleware\ReorderAlertMiddleware;

class GraphsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $base = base_path('Modules/Graphs');

        $this->mergeConfigFrom($base . '/Config/config.php', 'graphs');

        if (is_file($base . '/Config/menu.php')) {
            $this->mergeConfigFrom($base . '/Config/menu.php', 'graphs_menu');
        }

        if (is_file($base . '/Config/module_permissions.php')) {
            $this->mergeConfigFrom($base . '/Config/module_permissions.php', 'graphs.permissions');
        }

        // The current ERP can retain an older Nwidart module-provider manifest.
        // Explicitly register the module-owned route provider so that once the
        // Graphs provider is booted, its routes cannot disappear because of a
        // stale secondary-provider cache.
        if (! $this->app->getProvider(RouteServiceProvider::class)) {
            $this->app->register(RouteServiceProvider::class);
        }
    }

    public function boot(): void
    {
        $base = base_path('Modules/Graphs');

        $this->loadViewsFrom($base . '/Resources/views', 'graphs');

        // Primary/fallback route registration. RouteServiceProvider registers
        // the same file through Laravel's route-provider API. The name guard
        // keeps both paths safe if both providers are loaded.
        if (! Route::has('graphs.index') && ! $this->app->routesAreCached()) {
            $routeFile = $base . '/Routes/web.php';
            if (is_file($routeFile)) {
                Route::middleware([
                    'web',
                    'tenant.context',
                    'auth',
                    'language',
                    'SetSessionData',
                    'DayEnd',
                ])->group($routeFile);
            }
        }

        // Standalone login-time reorder alert. No host Blade/controller change.
        try {
            $this->app['router']->pushMiddlewareToGroup('web', ReorderAlertMiddleware::class);
        } catch (\Throwable $e) {
            // Analytics must never block the host ERP if middleware registration
            // is unavailable on a particular deployment.
        }
    }
}
