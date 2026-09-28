<?php

namespace Modules\Graphs\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->routes(function (): void {
            if (Route::has('graphs.index')) {
                return;
            }

            $routeFile = base_path('Modules/Graphs/Routes/web.php');
            if (! is_file($routeFile)) {
                return;
            }

            Route::middleware([
                'web',
                'tenant.context',
                'auth',
                'language',
                'SetSessionData',
                'DayEnd',
            ])->group($routeFile);
        });
    }
}
