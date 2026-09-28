<?php

namespace Modules\Suppliers\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Fail-safe module resource registration. Some deployments loaded the
        // route provider while omitting the main module provider, which caused
        // `No hint path defined for [suppliers]`.
        $views = module_path('Suppliers', 'Resources/views');
        $lang = module_path('Suppliers', 'Resources/lang');

        if (is_dir($views)) {
            $this->loadViewsFrom($views, 'suppliers');
        }
        if (is_dir($lang)) {
            $this->loadTranslationsFrom($lang, 'suppliers');
        }

        $this->routes(function (): void {
            if (Route::has('suppliers.records.index')) {
                return;
            }

            $file = module_path('Suppliers', 'Routes/web.php');
            if (is_file($file)) {
                require $file;
            }
        });
    }
}
