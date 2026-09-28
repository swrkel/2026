<?php

namespace Modules\Ran\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        if ($this->app->routesAreCached() || $this->app->bound('ran.routes.loaded')) {
            return;
        }
        $this->app->instance('ran.routes.loaded', true);

        foreach (['web.php', 'inventory.php', 'production.php', 'sales.php', 'reports.php'] as $file) {
            $path = module_path('Ran', 'Routes/'.$file);
            if (is_file($path)) {
                require $path;
            }
        }

        $public = module_path('Ran', 'Routes/public.php');
        if (is_file($public)) {
            Route::middleware('web')->group($public);
        }
    }
}
