<?php

namespace Modules\MembershipNew\app\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        MembershipNewRouteLoader::load();
    }
}
