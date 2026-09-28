<?php

namespace Modules\IdentityAccess\Providers;

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
        Route::middleware('web')->group(__DIR__ . '/../Routes/web.php');
        Route::middleware('api')->group(__DIR__ . '/../Routes/api.php');
    }
}
