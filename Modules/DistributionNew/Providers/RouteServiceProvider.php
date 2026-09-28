<?php

namespace Modules\DistributionNew\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function map(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix(config('distributionnew.route_prefix', 'distribution-new'))
            ->as('distributionnew.')
            ->namespace('Modules\\DistributionNew\\Http\\Controllers')
            ->group(__DIR__ . '/../Routes/web.php');
    }
}
