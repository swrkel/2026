<?php

namespace Modules\Reporting\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected $moduleNamespace =
        'Modules\Reporting\Http\Controllers';

    public function map()
    {
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes()
    {
        \Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(
                module_path(
                    'Reporting',
                    '/Routes/web.php'
                )
            );
    }
}