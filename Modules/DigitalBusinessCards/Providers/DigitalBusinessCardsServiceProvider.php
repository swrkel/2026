<?php

namespace Modules\DigitalBusinessCards\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\DigitalBusinessCards\Support\EventRecorder;
use Modules\DigitalBusinessCards\Support\QrCode;

class DigitalBusinessCardsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'DigitalBusinessCards';

    /** View namespace and publish tag prefix. */
    protected string $alias = 'dbc';

    protected ?string $root = null;

    public function register(): void
    {
        $this->mergeConfigFrom(
            $this->path('config', 'Config').'/digital-business-cards.php',
            'digital-business-cards'
        );

        $this->app->singleton(QrCode::class);
        $this->app->singleton(EventRecorder::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->path('database/migrations', 'Database/Migrations'));

        $this->registerViews();

        if (config('digital-business-cards.routes.enabled', true)) {
            $this->loadRoutesFrom($this->path('routes', 'Routes').'/web.php');
        }

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }
    }

    public function provides(): array
    {
        return [QrCode::class, EventRecorder::class];
    }

    /* ------------------------------------------------------------- Internals */

    /**
     * The module directory, found by walking up from this file until module.json
     * appears. This keeps the provider working whether it sits in app/Providers
     * (laravel-modules v11+) or Providers (v10 and earlier).
     */
    protected function moduleRoot(): string
    {
        if ($this->root !== null) {
            return $this->root;
        }

        $directory = __DIR__;

        while ($directory !== dirname($directory)) {
            if (is_file($directory.'/module.json')) {
                return $this->root = $directory;
            }

            $directory = dirname($directory);
        }

        // Fallback for installs without module.json: two levels up from Providers.
        return $this->root = dirname(__DIR__, 2);
    }

    /** Returns whichever casing of a module subdirectory actually exists. */
    protected function path(string $modern, string $legacy): string
    {
        $root = $this->moduleRoot();

        return is_dir($root.'/'.$modern) ? $root.'/'.$modern : $root.'/'.$legacy;
    }

    /**
     * Application overrides win: anything in resources/views/modules/dbc is
     * checked before the module's own views, so the host app can restyle the
     * public card without forking the module.
     */
    protected function registerViews(): void
    {
        $own      = $this->path('resources/views', 'Resources/views');
        $override = resource_path('views/modules/'.$this->alias);

        $this->loadViewsFrom(array_filter([
            is_dir($override) ? $override : null,
            $own,
        ]), $this->alias);
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            $this->path('config', 'Config').'/digital-business-cards.php' => config_path('digital-business-cards.php'),
        ], ['dbc-config', $this->moduleName]);

        $this->publishes([
            $this->path('resources/views', 'Resources/views') => resource_path('views/modules/'.$this->alias),
        ], ['dbc-views', $this->moduleName]);

        $this->publishes([
            $this->path('database/migrations', 'Database/Migrations') => database_path('migrations'),
        ], ['dbc-migrations', $this->moduleName]);
    }
}
