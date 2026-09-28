<?php

namespace Modules\PetroPDNew\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\PetroPDNew\Console\Commands\ImportClosedPoneShifts;
use Modules\PetroPDNew\Console\Commands\RetryPdnewIntegration;
use Modules\PetroPDNew\Http\Controllers\CompatibilityController;
use Modules\PetroPDNew\Http\Middleware\EnsurePdnewAccess;
use Modules\PetroPDNew\Http\Middleware\EnsurePdnewSchema;
use Modules\PetroPDNew\Http\Middleware\InitializePdnewTenantContext;
use Modules\PetroPDNew\Services\PdnewSchemaService;
use Modules\PetroPDNew\Support\RouteRegistrar;

class PetroPDNewServiceProvider extends ServiceProvider
{
    /** @var string */
    protected $moduleName = 'PetroPDNew';

    /** @var string */
    protected $moduleNameLower = 'petropdnew';

    public function register(): void
    {
        $root = base_path('Modules/PetroPDNew');
        $config = $root . '/Config/config.php';

        if (is_file($config)) {
            $this->mergeConfigFrom($config, $this->moduleNameLower);
        }

        $this->app->singleton(PdnewSchemaService::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportClosedPoneShifts::class,
                RetryPdnewIntegration::class,
            ]);
        }
    }

    public function boot(): void
    {
        $root = base_path('Modules/PetroPDNew');

        $this->loadViewsFrom($root . '/Resources/views', $this->moduleNameLower);
        $this->loadTranslationsFrom($root . '/Resources/lang', $this->moduleNameLower);
        $this->loadMigrationsFrom($root . '/Database/Migrations');

        $this->publishes([
            $root . '/Resources/assets' => public_path('modules/petropdnew'),
        ], 'petropdnew-public');

        // Register the real module pages first, then the historical URL aliases.
        $this->registerModuleRoutes();
        $this->registerCompatibilityRoutes();
    }

    /** Register the complete canonical route contract once per request. */
    private function registerModuleRoutes(): void
    {
        RouteRegistrar::register($this->app);
    }

    /**
     * Keep every historical /petropdnew URL working, including child pages
     * such as /petropdnew/operators, /reports, /settings and settlements.
     * A controller route is used instead of a closure so route caching works.
     */
    private function registerCompatibilityRoutes(): void
    {
        $canonicalPrefix = trim((string) config('petropdnew.route_prefix', 'petro-pd-new'), '/ ');

        if ($canonicalPrefix === '' || $canonicalPrefix === 'petropdnew' || Route::has('petro-pd-new.compat')) {
            return;
        }

        Route::match(['GET', 'HEAD'], '/petropdnew/{path?}', [CompatibilityController::class, 'redirect'])
            ->where('path', '.*')
            ->middleware('web')
            ->name('petro-pd-new.compat');
    }
}
