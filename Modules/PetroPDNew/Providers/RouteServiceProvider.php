<?php

namespace Modules\PetroPDNew\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Modules\PetroPDNew\Support\RouteRegistrar;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RouteRegistrar::register($this->app);
    }
}
