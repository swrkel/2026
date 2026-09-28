<?php

namespace Modules\PetroGeneral\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Modules\PetroGeneral\Support\PetroGeneralRouteRegistrar;

/**
 * Laravel 10 route bootstrap for the standalone Petro General module.
 */
class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->routes(static function (): void {
            PetroGeneralRouteRegistrar::register();
        });
    }
}
