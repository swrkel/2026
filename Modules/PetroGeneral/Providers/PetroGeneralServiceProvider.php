<?php

namespace Modules\PetroGeneral\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\PetroGeneral\Support\LegacyPetroCompatibility;
use Modules\PetroGeneral\Support\PetroGeneralRouteRegistrar;

class PetroGeneralServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerLegacyResourceNamespaces();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // IS2340: keep the central login identity stable on tenant-backed pages.
        // The middleware changes display fields only; tenant business/permission
        // attributes remain owned by the active database row. Registering it in
        // the web group also protects shared layouts used by Products New.
        if (! $this->app->bound('petrogeneral.central-user-display-middleware')) {
            $this->app->instance('petrogeneral.central-user-display-middleware', true);
            $this->app['router']->pushMiddlewareToGroup(
                'web',
                \Modules\PetroGeneral\Http\Middleware\SyncCentralUserDisplayIdentity::class
            );
        }

        // Register the installed route set after application boot as well.
        // This makes PetroGeneral plug-and-play even on a server that still
        // has an older generated Laravel route cache.
        $this->app->booted(static function (): void {
            PetroGeneralRouteRegistrar::register();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        LegacyPetroCompatibility::registerClassAliases();
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('petrogeneral.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'petrogeneral'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/petrogeneral');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $viewPaths = array_values(array_filter(
            array_merge(array_map(function ($path) {
            return $path . '/modules/petrogeneral';
        }, \Config::get('view.paths')), [$sourcePath]),
            'is_dir'
        ));

        $this->loadViewsFrom($viewPaths, 'petrogeneral');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/petrogeneral');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'petrogeneral');
        } else {
            $this->loadTranslationsFrom(__DIR__ .'/../Resources/lang', 'petrogeneral');
        }
    }

    /**
     * Retain old translation/view namespace consumers while the rest of the
     * application is migrated. Both namespaces are backed by PetroGeneral.
     */
    protected function registerLegacyResourceNamespaces(): void
    {
        if (LegacyPetroCompatibility::legacyModuleIsEnabled()) {
            return;
        }

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'petro');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'petro');
    }

    /**
     * Register an additional directory of factories.
     * 
     * @return void
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
