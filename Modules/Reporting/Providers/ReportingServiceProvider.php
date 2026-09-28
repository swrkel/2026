<?php

namespace Modules\Reporting\Providers;

use Illuminate\Support\ServiceProvider;

class ReportingServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(
            module_path('Reporting', 'Routes/web.php')
        );

        $this->loadViewsFrom(
            module_path('Reporting', 'Resources/views'),
            'reporting'
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }
}