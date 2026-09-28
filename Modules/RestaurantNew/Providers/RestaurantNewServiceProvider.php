<?php

namespace Modules\RestaurantNew\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\RestaurantNew\Http\Middleware\EnsureRestaurantNewAccess;
use Modules\RestaurantNew\Http\Middleware\EnsureRestaurantNewBusinessScope;
use Modules\RestaurantNew\Http\Middleware\EnsureRestaurantNewTenantScope;

class RestaurantNewServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'RestaurantNew';
    protected string $moduleNameLower = 'restaurantnew';

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->registerMigrations();
        $this->registerMiddlewareAliases();
    }

    public function register(): void
    {
        if (! $this->app->bound('restaurantnew.route_provider_registered')) {
            $this->app->instance('restaurantnew.route_provider_registered', true);
            $this->app->register(RouteServiceProvider::class);
        }
    }

    protected function registerConfig(): void
    {
        $path = __DIR__ . '/../Config/config.php';
        if (is_file($path)) {
            $this->mergeConfigFrom($path, $this->moduleNameLower);
        }
    }

    protected function registerViews(): void
    {
        $sourcePath = __DIR__ . '/../Resources/views';
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $paths = [];

        foreach ((array) config('view.paths', []) as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }

        $this->loadViewsFrom(array_merge($paths, [$sourcePath, $viewPath]), $this->moduleNameLower);
    }

    protected function registerTranslations(): void
    {
        foreach ([resource_path('lang/modules/' . $this->moduleNameLower), __DIR__ . '/../Lang'] as $path) {
            if (is_dir($path)) {
                $this->loadTranslationsFrom($path, $this->moduleNameLower);
            }
        }
    }

    protected function registerMigrations(): void
    {
        $path = __DIR__ . '/../Database/Migrations';
        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    protected function registerMiddlewareAliases(): void
    {
        $router = $this->app['router'];
        $existing = method_exists($router, 'getMiddleware') ? $router->getMiddleware() : [];

        $aliases = [
            'restaurantnew.business.scope' => EnsureRestaurantNewBusinessScope::class,
            'restaurantnew.business_access' => EnsureRestaurantNewBusinessScope::class,
            'restaurantnew.tenant.scope' => EnsureRestaurantNewTenantScope::class,
            'restaurantnew.scope' => EnsureRestaurantNewTenantScope::class,
            'restaurantnew.tenant' => EnsureRestaurantNewTenantScope::class,
            'restaurantnew.access' => EnsureRestaurantNewAccess::class,
            'restaurantnew.enabled' => EnsureRestaurantNewAccess::class,
            'restaurantnew.permission' => EnsureRestaurantNewAccess::class,
        ];

        foreach ($aliases as $alias => $middleware) {
            if (! isset($existing[$alias])) {
                $router->aliasMiddleware($alias, $middleware);
            }
        }
    }
}
