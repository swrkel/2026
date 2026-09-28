<?php
namespace Modules\EggManagement\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $base = ['web'];
        foreach ([
            'Stancl\\Tenancy\\Middleware\\InitializeTenancyByDomain',
            'Stancl\\Tenancy\\Middleware\\PreventAccessFromCentralDomains',
        ] as $class) {
            if (class_exists($class)) $base[] = $class;
        }
        $aliases = app('router')->getMiddleware();
        foreach (['IsInstalled','bootstrap','language','dynamic.no-store','timezone'] as $alias) {
            if (isset($aliases[$alias])) $base[] = $alias;
        }
        $base = array_values(array_unique($base));
        Route::middleware($base)->group(__DIR__.'/../Routes/public.php');

        $private = $base;
        foreach (['auth','SetSessionData','tenant.context','check.route.permission'] as $alias) {
            if ($alias === 'auth' || isset($aliases[$alias])) $private[] = $alias;
        }
        $private = array_values(array_unique($private));
        Route::middleware($private)->group(__DIR__.'/../Routes/web.php');
        Route::middleware($private)->group(__DIR__.'/../Routes/api.php');
    }
}
