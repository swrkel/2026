<?php

namespace Modules\Tailoring\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function map(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix(config('tailoring.route_prefix', 'tailoring'))
            ->name('tailoring.')
            ->group(__DIR__ . '/../Routes/web.php');
    }
}
