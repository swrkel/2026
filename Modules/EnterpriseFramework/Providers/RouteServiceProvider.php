<?php

namespace Modules\EnterpriseFramework\Providers;

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
        Route::middleware(config('enterpriseframework.middleware', ['web', 'auth']))
            ->prefix(config('enterpriseframework.route_prefix', 'enterprise-framework'))
            ->name('enterprise-framework.')
            ->group(__DIR__ . '/../Routes/web.php');
    }
}
