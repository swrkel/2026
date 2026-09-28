<?php

/*
|--------------------------------------------------------------------------
| Products New module bootstrap
|--------------------------------------------------------------------------
|
| Some installations load module start.php before the module registry is
| refreshed. Register only the module provider here. Route loading remains
| protected by the shared productsnew.routes_loaded guard.
|
*/

if (! app()->bound('productsnew.service_provider_registered')
    && class_exists(\Modules\ProductsNew\Providers\ProductsNewServiceProvider::class)) {
    app()->instance('productsnew.service_provider_registered', true);
    app()->register(\Modules\ProductsNew\Providers\ProductsNewServiceProvider::class);
}

if (! app()->bound('productsnew.route_provider_registered')
    && class_exists(\Modules\ProductsNew\Providers\RouteServiceProvider::class)) {
    app()->instance('productsnew.route_provider_registered', true);
    app()->register(\Modules\ProductsNew\Providers\RouteServiceProvider::class);
}
