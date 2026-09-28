<?php

namespace Modules\Subscription\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Factory;
use Modules\Subscription\Console\SendEstateSubscriptionReminders;

class SubscriptionServiceProvider extends ServiceProvider
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
        // Register module routes from the provider as well as the legacy module
        // start.php bootstrap. Some installations do not execute module files
        // early enough for Blade sidebars that resolve named routes. The guard
        // keeps this idempotent and also recovers safely from a stale route cache.
        $this->registerModuleRoutes();

        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->commands([
            SendEstateSubscriptionReminders::class,
        ]);

        // Idempotent reminder runner. Reminder logs prevent duplicate SMS even
        // though the scheduler checks hourly.
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('subscription:send-estate-reminders')->hourly()->withoutOverlapping();
        });
    }


    /**
     * Register the Subscription routes in an idempotent way.
     *
     * The module.json still contains start.php for compatibility with the
     * existing modular loader.  This provider-level fallback guarantees that
     * named routes exist before any Subscription Blade layout is rendered.
     *
     * @return void
     */
    protected function registerModuleRoutes()
    {
        $router = $this->app->make('router');

        if (!$router->has('subscription.estate.index')) {
            require __DIR__ . '/../Http/routes.php';
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('subscription.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'subscription'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/subscription');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $viewPaths = array_values(array_filter(
            array_merge(array_map(function ($path) {
            return $path . '/modules/subscription';
        }, \Config::get('view.paths')), [$sourcePath]),
            'is_dir'
        ));

        $this->loadViewsFrom($viewPaths, 'subscription');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/subscription');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'subscription');
        } else {
            $this->loadTranslationsFrom(__DIR__ .'/../Resources/lang', 'subscription');
        }
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
