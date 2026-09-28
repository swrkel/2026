<?php

namespace Modules\HRManager\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class HRManagerMenuServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $hrMenu = config('hrmanager_menu', require module_path('HRManager', 'Config/menu.php'));
            $view->with('hrManagerMenu', $hrMenu);
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(module_path('HRManager', 'Config/menu.php'), 'hrmanager_menu');
    }
}
