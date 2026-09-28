<?php

namespace Modules\POS\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/pos-module';

    public function boot()
    {
        parent::boot();
    }

    public function map()
    {
        // The legacy Modules/POS/Http/routes.php loader and this provider may
        // both exist in older deployments. One shared flag prevents the same
        // route files from being loaded twice.
        if ($this->app->routesAreCached() || $this->app->bound('pos.routes.loaded')) {
            return;
        }

        $this->app->instance('pos.routes.loaded', true);

        foreach ([
            'web.php',
            'pos_page_003.php',
            'pos006_010.php',
            'pos016_020.php',
            'reports.php',
            'kitchen.php',
            'pagefix_v5.php',
        ] as $routeFile) {
            $path = __DIR__ . '/../Routes/' . $routeFile;
            if (is_file($path)) {
                // Each POS route file already declares its own web/auth
                // middleware. Do not wrap it again.
                require $path;
            }
        }

        $apiPath = __DIR__ . '/../Routes/api.php';
        if (is_file($apiPath)) {
            Route::prefix('api')->middleware('api')->group($apiPath);
        }
    }
}
