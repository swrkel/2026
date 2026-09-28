<?php

namespace Modules\ChurchManagement\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Church Management service provider.
 *
 * The module is self-contained: its own views, translations, migrations, routes
 * and configuration, all namespaced under `churchmanagement`. It reads nothing
 * from another module, so it can be removed from Modules/ without leaving a
 * broken reference anywhere else.
 *
 * What it DOES share with the application, deliberately, is the ERP login
 * session and the outer layout. That was the decision on this ticket: staff sign
 * in once and reach Church Management from the same sidebar as everything else.
 * Everything below that line is the module's own.
 */
class ChurchManagementServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'churchmanagement');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'churchmanagement');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->loadModuleRoutes();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'churchmanagement');
        $this->mergeConfigFrom(__DIR__ . '/../Config/menu.php', 'churchmanagement_menu');
    }

    /**
     * Each route file is loaded only if present, so the module still boots while
     * a later phase's routes are still being written.
     */
    protected function loadModuleRoutes(): void
    {
        foreach (['web.php'] as $file) {
            $path = __DIR__ . '/../Routes/' . $file;
            if (file_exists($path)) {
                $this->loadRoutesFrom($path);
            }
        }

        $apiPath = __DIR__ . '/../Routes/api.php';
        if (file_exists($apiPath)) {
            Route::prefix('api')->middleware('api')->group($apiPath);
        }
    }
}
