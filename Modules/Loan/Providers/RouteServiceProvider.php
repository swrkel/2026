<?php

namespace Modules\Loan\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Controller namespace for the Loan module.
     */
    protected $moduleNamespace = 'Modules\Loan\Http\Controllers';

    /**
     * Define route bindings and patterns.
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Register Loan module routes.
     */
    public function map()
    {
        $this->mapWebRoutes();
    }

    /**
     * Register web routes for the standalone Loan module.
     *
     * LOAN-37 note:
     * The actual route group middleware, prefix, and namespace are kept inside
     * Modules/Loan/Routes/web.php so the route file remains the single readable
     * place for Loan routing rules.
     */
    protected function mapWebRoutes()
    {
        Route::namespace($this->moduleNamespace)
            ->group(module_path('Loan', 'Routes/web.php'));
    }
}
