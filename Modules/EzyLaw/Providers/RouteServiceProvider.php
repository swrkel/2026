<?php
namespace Modules\EzyLaw\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();
        $this->routes(function (): void {
            if ($this->app->bound('ezylaw.routes_loaded') || Route::has('ezylaw.dashboard')) {
                return;
            }
            $this->app->instance('ezylaw.routes_loaded', true);
            require __DIR__.'/../Routes/web.php';
        });
    }
}
