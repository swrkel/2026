<?php

namespace Modules\Audit\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * AUD-BOOT-005
     *
     * This intentionally matches the route-provider pattern already working in
     * this ERP's standalone modules: register the route callback through
     * $this->routes() and do not call parent::boot().
     */
    public function boot(): void
    {
        $this->routes(function (): void {
            if (Route::has('audit.dashboard')) {
                return;
            }

            $routeFile = module_path('Audit', 'Routes/web.php');

            if (is_file($routeFile)) {
                require $routeFile;
            }
        });
    }
}
