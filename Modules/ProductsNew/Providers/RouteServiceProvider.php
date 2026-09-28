<?php

namespace Modules\ProductsNew\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->routes(function (): void {
            if (! $this->app->bound('productsnew.routes_loaded')
                && ! Route::has('products-new.dashboard')) {
                $this->app->instance('productsnew.routes_loaded', true);

                foreach (['web.php', 'reports.php'] as $routeFile) {
                    $path = __DIR__ . '/../Routes/' . $routeFile;
                    if (is_file($path)) {
                        require $path;
                    }
                }
            }

            if (! $this->app->bound('productsnew.api_routes_loaded')
                && ! Route::has('api.products-new.lookup')) {
                $this->app->instance('productsnew.api_routes_loaded', true);

                $apiPath = __DIR__ . '/../Routes/api.php';
                if (is_file($apiPath)) {
                    Route::prefix('api')
                        ->middleware('api')
                        ->group($apiPath);
                }
            }
        });
    }
}
