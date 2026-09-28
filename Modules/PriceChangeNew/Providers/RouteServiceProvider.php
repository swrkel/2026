<?php

namespace Modules\PriceChangeNew\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->routes(function (): void {
            if ($this->app->bound('pricechangenew.routes_loaded')
                || Route::has('pricechangenew.dashboard')) {
                return;
            }

            $this->app->instance('pricechangenew.routes_loaded', true);
            $path = __DIR__ . '/../Routes/web.php';
            if (is_file($path)) {
                require $path;
            }
        });
    }
}
