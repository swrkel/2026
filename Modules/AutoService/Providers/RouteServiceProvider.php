<?php

namespace Modules\AutoService\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected $moduleNamespace = 'Modules\\AutoService\\Http\\Controllers';

    public function map()
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('AutoService', '/Routes/web.php'));

        $stage046Routes = module_path('AutoService', 'Routes/stage046.php');

        if (file_exists($stage046Routes)) {
            require $stage046Routes;
        }
    }
}
