<?php
namespace Modules\RestaurantNew\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Middleware\EnsureRestaurantSchema;
use Modules\RestaurantNew\Http\Middleware\InitializeRestaurantTenantContext;

final class RouteRegistrar
{
    public static function register(Application $app): void
    {
        if (Route::has('restaurant-new.dashboard')) {
            $app->instance('restaurantnew.routes_loaded', true);
            return;
        }
        $file = dirname(__DIR__) . '/Routes/web.php';
        if (!is_file($file)) return;
        $prefix = trim((string) config('restaurantnew.route_prefix', 'restaurant-new'), '/ ') ?: 'restaurant-new';
        $name = trim((string) config('restaurantnew.route_name', 'restaurant-new'), '. ') ?: 'restaurant-new';
        Route::middleware([
            'web',
            InitializeRestaurantTenantContext::class,
            'auth',
            EnsureRestaurantSchema::class,
        ])->prefix($prefix)->name($name . '.')->group($file);
        $app->instance('restaurantnew.routes_loaded', true);
    }
}
