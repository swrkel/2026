<?php

namespace Modules\PetroDirectNew\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\PetroDirectNew\Console\Commands\InstallPetroDirectNew;

class PetroDirectNewServiceProvider extends ServiceProvider
{
    protected $moduleName = 'PetroDirectNew';
    protected $moduleNameLower = 'petrodirectnew';

    public function boot(): void
    {
        $this->app['router']->aliasMiddleware('petrodirectnew.schema', \Modules\PetroDirectNew\Http\Middleware\EnsurePetroDirectNewSchema::class);
        $this->registerTranslations();
        $this->registerViews();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([InstallPetroDirectNew::class]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'petrodirectnew');
    }

    protected function registerRoutes(): void
    {
        $routeFile = __DIR__ . '/../Routes/web.php';
        if (!is_file($routeFile)) {
            return;
        }

        Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone', 'tenant.context', 'petrodirectnew.schema'])
            ->prefix(config('petrodirectnew.route_prefix', 'petro-direct-new'))
            ->as(config('petrodirectnew.route_name_prefix', 'petro-direct-new.'))
            ->group($routeFile);

        foreach ((array) config('petrodirectnew.compatibility_prefixes', []) as $compatibilityPrefix) {
            $compatibilityPrefix = trim((string) $compatibilityPrefix, '/ ');
            if ($compatibilityPrefix === '') {
                continue;
            }
            Route::middleware(['web', 'auth'])
                ->get('/' . $compatibilityPrefix, function () {
                    return redirect()->route('petro-direct-new.dashboard');
                });
        }
    }

    protected function registerViews(): void
    {
        $sourcePath = __DIR__ . '/../Resources/views';
        $this->loadViewsFrom($sourcePath, $this->moduleNameLower);
    }

    protected function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', $this->moduleNameLower);
    }
}
