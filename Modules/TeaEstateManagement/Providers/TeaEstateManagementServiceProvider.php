<?php
namespace Modules\TeaEstateManagement\Providers;

use Illuminate\Support\ServiceProvider;

class TeaEstateManagementServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'teaestate');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'teaestate');
        // Operational tea_* migrations are intentionally NOT auto-loaded here.
        // This ERP has a central DB plus tenant DBs; auto-loading could create
        // operational Tea tables in the central database during a normal migrate.
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'teaestatemanagement');
        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'teaestatemanagement_menu');
    }
}
