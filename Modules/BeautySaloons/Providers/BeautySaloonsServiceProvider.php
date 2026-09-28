<?php

namespace Modules\BeautySaloons\Providers;

use Illuminate\Support\ServiceProvider;

class BeautySaloonsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modulePath = dirname(__DIR__);
        $this->loadRoutesFrom($modulePath . '/Routes/web.php');
        $this->loadViewsFrom($modulePath . '/Resources/views', 'beautysaloons');
        $this->loadTranslationsFrom($modulePath . '/Resources/lang', 'beautysaloons');
        $this->loadMigrationsFrom($modulePath . '/Database/Migrations');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/Config/config.php', 'beautysaloons');
    }
}
