<?php

namespace Modules\AirlineTicketingNew\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\\AirlineTicketingNew\\Http\\Controllers';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapWebRoutes();
        $this->mapApiRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('AirlineTicketingNew', 'Routes/web.php'));
    }

    protected function mapApiRoutes(): void
    {
        Route::prefix('api/airline-ticketing-new')
            ->middleware('api')
            ->namespace($this->moduleNamespace . '\\Api')
            ->as('api.airline-ticketing-new.')
            ->group(module_path('AirlineTicketingNew', 'Routes/api.php'));
    }
}
