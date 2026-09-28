<?php

namespace Modules\RestaurantNew\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /*
     * IMPORTANT:
     * Do not set the inherited $namespace property here.
     *
     * Laravel's base RouteServiceProvider uses that property as the root
     * controller namespace for URL generation. In a modular application,
     * setting it to Modules\\RestaurantNew\\Http\\Controllers causes host
     * views that use action('BusinessController@clearCache'),
     * action('PrinterController@index'), etc. to be rewritten as
     * RestaurantNew controller actions and can break every page that renders
     * the global header/sidebar.
     *
     * RestaurantNew route files use fully-qualified controller class names,
     * so no controller namespace is required here.
     */

    public function map(): void
    {
        $routePath = __DIR__ . '/../Routes';
        if (! is_dir($routePath)) {
            return;
        }

        $files = glob($routePath . '/*.php') ?: [];
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($files as $path) {
            $file = basename($path);

            if ($file === 'api.php') {
                Route::prefix('api')->middleware('api')->group($path);
                continue;
            }

            Route::middleware('web')->group($path);
        }
    }
}
