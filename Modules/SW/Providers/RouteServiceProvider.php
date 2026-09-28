<?php

namespace Modules\SW\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function map(): void
    {
        Route::middleware('web')
            ->namespace('Modules\SW\Http\Controllers')
            ->group(module_path('SW', 'Routes/web.php'));
    }
}
