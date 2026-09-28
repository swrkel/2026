<?php

namespace Modules\PetroGeneral\Support;

use Illuminate\Support\Facades\Route;

/**
 * Keeps old cross-module class references operational after the legacy Petro
 * module is disabled. The aliases are lazy and resolve to PetroGeneral-owned
 * classes; no file is loaded from Modules/Petro.
 */
final class LegacyPetroCompatibility
{
    private static bool $registered = false;

    public static function legacyModuleIsEnabled(): bool
    {
        $statusesPath = base_path('modules_statuses.json');

        if (! is_file($statusesPath)) {
            return false;
        }

        $statuses = json_decode((string) file_get_contents($statusesPath), true);

        return is_array($statuses) && ! empty($statuses['Petro']);
    }

    public static function registerClassAliases(): void
    {
        if (self::$registered || self::legacyModuleIsEnabled()) {
            return;
        }

        self::$registered = true;

        spl_autoload_register(static function (string $requestedClass): void {
            $legacyRoot = 'Modules\\'.'Petro\\';

            if (! str_starts_with($requestedClass, $legacyRoot)) {
                return;
            }

            $relativeClass = substr($requestedClass, strlen($legacyRoot));

            if ($relativeClass === 'Entities\\Concerns\\RequiresReconcilerContext') {
                require_once __DIR__.'/legacy/RequiresReconcilerContext.php';

                return;
            }

            $candidates = [
                'Modules\\PetroGeneral\\'.$relativeClass,
            ];

            // A small number of older callers omitted the Http segment.
            if (str_starts_with($relativeClass, 'Controllers\\')) {
                $candidates[] = 'Modules\\PetroGeneral\\Http\\'.$relativeClass;
            }

            foreach ($candidates as $candidate) {
                $available = class_exists($candidate)
                    || interface_exists($candidate)
                    || trait_exists($candidate);

                if ($available && ! class_exists($requestedClass, false) && ! interface_exists($requestedClass, false) && ! trait_exists($requestedClass, false)) {
                    class_alias($candidate, $requestedClass);

                    return;
                }
            }
        }, true, true);
    }

    /**
     * Mirror PetroGeneral controller routes under the former /petro prefix.
     *
     * These compatibility routes deliberately have no names. They exist so
     * action('\Modules\Petro\Http\Controllers\...') calls in older, separate
     * modules keep resolving after Petro is disabled. The mirrored actions
     * are served by PetroGeneral class aliases and never load Modules/Petro.
     */
    public static function registerLegacyRoutes(): void
    {
        if (self::legacyModuleIsEnabled()) {
            return;
        }

        $registered = [];
        $routes = array_values(Route::getRoutes()->getRoutes());

        foreach ($routes as $route) {
            $uri = ltrim($route->uri(), '/');

            if ($uri !== 'petro-general' && ! str_starts_with($uri, 'petro-general/')) {
                continue;
            }

            $action = $route->getActionName();

            if (
                $action === 'Closure'
                || ! str_starts_with($action, 'Modules\\PetroGeneral\\Http\\Controllers\\')
            ) {
                continue;
            }

            $legacyUri = 'petro'.substr($uri, strlen('petro-general'));
            $legacyAction = 'Modules\\Petro\\Http\\Controllers\\'
                .substr($action, strlen('Modules\\PetroGeneral\\Http\\Controllers\\'));
            $methods = array_values(array_unique($route->methods()));
            $signature = implode('|', $methods).'|'.$legacyUri.'|'.$legacyAction;

            if (isset($registered[$signature])) {
                continue;
            }

            $registered[$signature] = true;
            $legacyRoute = Route::match($methods, $legacyUri, $legacyAction)
                ->middleware($route->gatherMiddleware());

            if (! empty($route->wheres)) {
                $legacyRoute->where($route->wheres);
            }
        }
    }
}
