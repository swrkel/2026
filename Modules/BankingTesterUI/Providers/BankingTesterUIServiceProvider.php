<?php

namespace Modules\BankingTesterUI\Providers;

use Illuminate\Support\ServiceProvider;

class BankingTesterUIServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $base = dirname(__DIR__);
        $this->loadRoutesFrom($base . '/Routes/web.php');
        $this->loadViewsFrom($base . '/Resources/views', 'bankingtesterui');
        $this->loadTranslationsFrom($base . '/lang', 'bankingtesterui');
        $this->loadMigrationsFrom($base . '/Database/Migrations');
        $this->publishes([$base . '/public' => public_path('modules/bankingtesterui')], 'public');
    }

    public function register(): void
    {
        $this->app->singleton(\Modules\BankingTesterUI\Services\BankingTesterNavigationService::class);
    }
}
