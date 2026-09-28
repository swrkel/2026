<?php

namespace Modules\PetroPDNew\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Modules\PetroPDNew\Http\Middleware\EnsurePdnewAccess;
use Modules\PetroPDNew\Http\Middleware\EnsurePdnewSchema;
use Modules\PetroPDNew\Http\Middleware\InitializePdnewTenantContext;

final class RouteRegistrar
{
    /**
     * Versioned per-request key. An older or partial route bootstrap must not
     * prevent the current complete route contract from being registered.
     */
    private const REGISTRATION_KEY = 'petropdnew.routes.contract.20260804.1';

    /**
     * Critical routes used while rendering the main module pages.
     * Missing any of these means the deployed route file is incomplete.
     */
    private const CRITICAL_ROUTES = [
        'petro-pd-new.dashboard',
        'petro-pd-new.sources.index',
        'petro-pd-new.settlements.index',
        'petro-pd-new.day-ends.index',
        'petro-pd-new.operators.index',
        'petro-pd-new.operators.tab',
        'petro-pd-new.operators.sync',
        'petro-pd-new.reports.index',
        'petro-pd-new.integration.index',
        'petro-pd-new.notifications.index',
        'petro-pd-new.audit.index',
        'petro-pd-new.settings.edit',
    ];

    public static function register(Application $app): void
    {
        if ($app->bound(self::REGISTRATION_KEY)) {
            return;
        }

        // Mark first so a second provider cannot recursively register the same
        // routes during the same request.
        $app->instance(self::REGISTRATION_KEY, true);

        if ($app->routesAreCached()) {
            return;
        }

        $root = dirname(__DIR__);
        $routes = $root . '/Routes/web.php';
        $prefix = trim((string) config('petropdnew.route_prefix', 'petro-pd-new'), '/ ');
        $name = trim((string) config('petropdnew.route_name', 'petro-pd-new.'), '. ') . '.';

        if ($prefix === '' || !is_file($routes)) {
            Log::critical('Petro PD-New routes could not be registered.', [
                'route_file' => $routes,
                'route_prefix' => $prefix,
            ]);

            return;
        }

        /*
         * Always load the complete current route file once per request.
         * Do not skip merely because an older bootstrap registered the
         * dashboard route; that was the cause of partially missing routes such
         * as petro-pd-new.operators.tab.
         */
        Route::middleware([
            'web',
            InitializePdnewTenantContext::class,
            'auth',
            EnsurePdnewSchema::class,
            EnsurePdnewAccess::class,
        ])
            ->prefix($prefix)
            ->name($name)
            ->group($routes);

        /*
         * MA-002 HOTFIX.
         *
         * A previous revision of this file called
         *     Route::getRoutes()->refreshNameLookup();
         * here. That method does not exist on RouteCollection in the Laravel
         * version this application runs on, so every request through this
         * registrar threw:
         *     Call to undefined method
         *     Illuminate\Routing\RouteCollection::refreshNameLookup()
         * That call has been removed. Nothing here touches the framework's
         * internals any more.
         *
         * The original problem it was trying to solve is still solved, but
         * without the framework dependency - see missingCriticalRoutes().
         */
        $missing = self::missingCriticalRoutes();
        if ($missing !== []) {
            Log::critical('Petro PD-New route contract is incomplete after registration.', [
                'missing_routes' => $missing,
                'route_file' => $routes,
            ]);
        }
    }

    /** @return array<int, string> */
    public static function missingCriticalRoutes(): array
    {
        /*
         * MA-002: read the names straight off the Route objects instead of
         * asking Route::has().
         *
         * Route::has() consults RouteCollection::$nameList, which is filled
         * when a route is ADDED, using the name it holds at that instant.
         * These routes are declared as
         *     Route::get(...)->name('dashboard');
         * so they are added first and named afterwards, and the name never
         * reaches that list. The framework normally repairs this later in the
         * request; this registrar checks immediately, so every route looked
         * missing and produced a CRITICAL log entry on each request - 158 in
         * a single day, all false.
         *
         * Iterating the collection asks each Route for its real name, so the
         * result is correct no matter when the check runs, and it uses only
         * public API that exists across Laravel versions. getRoutes() returns
         * an IteratorAggregate, and getName() is stable public API.
         */
        $registered = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (is_string($name) && $name !== '') {
                $registered[$name] = true;
            }
        }

        return array_values(array_filter(
            self::CRITICAL_ROUTES,
            static fn (string $routeName): bool => ! isset($registered[$routeName])
        ));
    }
}
