<?php

namespace Modules\Loan\Providers;

use Illuminate\Support\ServiceProvider;

class LoanServiceProvider extends ServiceProvider
{
    /**
     * Boot the Loan module service provider.
     *
     * LOAN-37 note:
     * Routes are intentionally not loaded here.
     * Route loading belongs to Modules\Loan\Providers\RouteServiceProvider only.
     * Loading routes from both providers can register duplicate route definitions,
     * duplicate route names, and duplicate sidebar/menu behaviour during route cache.
     */
    public function boot()
    {
        $this->loadViewsFrom(
            module_path('Loan', 'Resources/views'),
            'loan'
        );

        $this->loadTranslationsFrom(
            module_path('Loan', 'Resources/lang'),
            'loan'
        );

        $this->loadMigrationsFrom(
            module_path('Loan', 'Database/Migrations')
        );
    }

    /**
     * Register module services.
     */
    public function register()
    {
        // Keep this provider light. Bind Loan services here only when needed.
    }
}
