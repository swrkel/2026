<?php

namespace Modules\PetroGeneral\Support;

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;

/**
 * Registers the installed Petro General routes exactly once.
 *
 * The registration deliberately does not stop when Laravel has a route cache.
 * Petro General is deployed as a plug-and-play module and the installed route
 * set must therefore be able to replace stale cached page/AJAX definitions.
 */
final class PetroGeneralRouteRegistrar
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        $before = count(Route::getRoutes()->getRoutes());
        require __DIR__.'/../Http/routes.php';
        $all = array_values(Route::getRoutes()->getRoutes());
        $installedRoutes = array_slice($all, $before);

        if (! LegacyPetroCompatibility::legacyModuleIsEnabled()) {
            LegacyPetroCompatibility::registerLegacyRoutes();

            if (! Route::has('petrogeneral.legacy-endpoint')) {
                Route::any('/petro/{path?}', static function (?string $path = null) {
                    $target = url('/petro-general'.($path ? '/'.$path : ''));
                    $query = request()->getQueryString();

                    if ($query) {
                        $target .= '?'.$query;
                    }

                    return redirect()->to($target, 307);
                })->where('path', '.*')->name('petrogeneral.legacy-endpoint');
            }
        }

        self::registerCollisionProofLiveRoutes($installedRoutes);

        $routes = Route::getRoutes();
        if (method_exists($routes, 'refreshNameLookups')) {
            $routes->refreshNameLookups();
        }
        if (method_exists($routes, 'refreshActionLookups')) {
            $routes->refreshActionLookups();
        }
    }

    /**
     * Mirror every newly installed /petro-general route under
     * /petro-general-live. The mirrored route keeps the original route name,
     * so route() helpers resolve to the collision-proof family. Exact old URLs
     * remain registered too for hard-coded AJAX links/bookmarks.
     */
    private static function registerCollisionProofLiveRoutes(array $installedRoutes): void
    {
        foreach ($installedRoutes as $source) {
            if (! $source instanceof LaravelRoute) {
                continue;
            }

            $uri = ltrim($source->uri(), '/');
            if ($uri !== 'petro-general' && ! str_starts_with($uri, 'petro-general/')) {
                continue;
            }

            $suffix = substr($uri, strlen('petro-general'));
            $uses = $source->getAction('uses');

            if ($uses === null) {
                continue;
            }

            $live = Route::match($source->methods(), 'petro-general-live'.$suffix, $uses);

            $middleware = $source->gatherMiddleware();
            if (! empty($middleware)) {
                $live->middleware($middleware);
            }

            if (! empty($source->wheres)) {
                $live->where($source->wheres);
            }

            $name = $source->getName();
            if (is_string($name) && $name !== '') {
                $live->name($name);
            }
        }
    }
}
