<?php
namespace Modules\HotelManagement\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class HotelManagementServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'hotelmanagement');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'hotelmanagement');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadModuleRoutes();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'hotelmanagement');
        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'hotelmanagement_menu');
    }

    protected function loadModuleRoutes(): void
    {
        foreach (['web.php', 'operations.php', 'reports.php', 'dashboard.php', 'portal.php'] as $file) {
            $path = __DIR__.'/../Routes/'.$file;
            if (file_exists($path)) {
                $this->loadRoutesFrom($path);
            }
        }

        $apiPath = __DIR__.'/../Routes/api.php';
        if (file_exists($apiPath)) {
            Route::prefix('api')->middleware('api')->group($apiPath);
        }
    }
}
