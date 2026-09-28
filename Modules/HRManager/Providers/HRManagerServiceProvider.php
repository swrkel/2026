<?php
namespace Modules\HRManager\Providers;
use Illuminate\Support\ServiceProvider;
class HRManagerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(module_path('HRManager','Resources/views'), 'hrmanager');
        $this->loadMigrationsFrom(module_path('HRManager','Database/Migrations'));
    }
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
