<?php

namespace Modules\Petro\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class PetroServiceProvider extends ServiceProvider
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
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->registerPetroRouteRecovery();
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
     * Recover Petro routes when a transferred installation is using an old or
     * partial route cache, or when the legacy module start file was not run.
     *
     * Direct Settlement and List Direct Settlement are restored from the full
     * route file because those screens also require their AJAX, payment, print
     * and edit endpoints. The small global vehicle route set is checked
     * separately on every boot so a missing sidebar action cannot take the
     * whole application down with an HTTP 500 response.
     *
     * @return void
     */
    protected function registerPetroRouteRecovery()
    {
        $this->app->booted(function () {
            if (! $this->app->bound('router')) {
                return;
            }

            $router = $this->app->make('router');

            $hasListRoute = $this->hasGetRouteUri($router, 'petro/settlement');
            $hasCreateRoute = $this->hasGetRouteUri($router, 'petro/settlement/create');

            if (! $hasListRoute || ! $hasCreateRoute) {
                $routeFile = __DIR__ . '/../Http/routes.php';

                if (is_file($routeFile)) {
                    require $routeFile;
                }
            }

            // A transferred installation can contain a partial/stale route cache:
            // the main Petro settlement routes may be present while the global
            // sidebar's VehicleController@vehicles_list action is absent.  In
            // that case action() throws during sidebar rendering and every page
            // fails with HTTP 500. Recover only the small vehicle route set so
            // that existing cached Petro routes are not registered a second time.
            $this->registerMissingVehicleRoutes($router);
        });
    }

    /**
     * Register the Petro vehicle routes when a partial route cache omitted them.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    protected function registerMissingVehicleRoutes($router)
    {
        $middleware = [
            'web',
            'auth',
            'language',
            'SetSessionData',
            'DayEnd',
            'tenant.context',
            \Modules\Petro\Http\Middleware\EnsurePetroModuleEnabled::class,
        ];

        $listAction = 'Modules\\Petro\\Http\\Controllers\\VehicleController@vehicles_list';

        if (! $this->hasRouteAction($router, $listAction, 'GET')) {
            $router->get('/vehicles', [
                'middleware' => $middleware,
                'uses' => $listAction,
                'as' => 'petro.vehicles.list',
            ]);
        }

        $indexAction = 'Modules\\Petro\\Http\\Controllers\\VehicleController@index';

        if (! $this->hasRouteAction($router, $indexAction, 'GET')) {
            $router->get('/vehicle', [
                'middleware' => $middleware,
                'uses' => $indexAction,
                'as' => 'petro.vehicle.index',
            ]);
        }

        $editAction = 'Modules\\Petro\\Http\\Controllers\\VehicleController@edit';

        if (! $this->hasRouteAction($router, $editAction, 'GET')) {
            $router->get('/vehicle/edit/{id}', [
                'middleware' => $middleware,
                'uses' => $editAction,
                'as' => 'vehicle.editVehicle',
            ]);
        }

        $updateAction = 'Modules\\Petro\\Http\\Controllers\\VehicleController@update';

        if (! $this->hasRouteAction($router, $updateAction, 'POST')) {
            $router->post('/vehicle/update/{id}', [
                'middleware' => $middleware,
                'uses' => $updateAction,
                'as' => 'petro.vehicle.updateVehicle',
            ]);
        }
    }

    /**
     * Determine whether a controller action is already present in the routes.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @param  string  $action
     * @param  string  $method
     * @return bool
     */
    protected function hasRouteAction($router, $action, $method)
    {
        $expectedAction = ltrim($action, '\\');
        $expectedMethod = strtoupper($method);

        foreach ($router->getRoutes() as $route) {
            if (
                ltrim($route->getActionName(), '\\') === $expectedAction
                && in_array($expectedMethod, $route->methods(), true)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether a GET route with the given URI is already registered.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @param  string  $uri
     * @return bool
     */
    protected function hasGetRouteUri($router, $uri)
    {
        $expectedUri = trim($uri, '/');

        foreach ($router->getRoutes() as $route) {
            if (
                trim($route->uri(), '/') === $expectedUri
                && in_array('GET', $route->methods(), true)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('petro.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'petro'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/petro');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $viewPaths = array_values(array_filter(
            array_merge(array_map(function ($path) {
            return $path . '/modules/petro';
        }, \Config::get('view.paths')), [$sourcePath]),
            'is_dir'
        ));

        $this->loadViewsFrom($viewPaths, 'petro');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/petro');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'petro');
        } else {
            $this->loadTranslationsFrom(__DIR__ .'/../Resources/lang', 'petro');
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
