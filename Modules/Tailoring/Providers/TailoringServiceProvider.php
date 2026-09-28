<?php

namespace Modules\Tailoring\Providers;

use Illuminate\Support\ServiceProvider;

class TailoringServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'tailoring');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'tailoring');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'tailoring');
    }
}
