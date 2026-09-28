<?php

namespace Modules\StockTakingNew\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Modules\StockTakingNew\Support\RouteRegistrar;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        RouteRegistrar::register($this->app);
    }
}
