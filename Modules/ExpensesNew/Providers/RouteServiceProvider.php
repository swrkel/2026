<?php

namespace Modules\ExpensesNew\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Modules\ExpensesNew\Http\Middleware\EnsureExpensesNewSchema;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register the complete Expenses New route collection exactly once.
     *
     * The supplied module wrapped Routes/web.php with /expenses-new while the
     * route file wrapped itself with the same prefix. That generated URLs such
     * as /expenses-new/expenses-new/reports and left every other controller
     * without a route. This provider owns the prefix and middleware once.
     */
    public function boot(): void
    {
        parent::boot();

        /* LA-1152: 'tenant.context' MUST run before 'auth'.

                 SetTenantContext calls tenancy()->initialize(), which switches the
                 default database connection to the tenant. With 'auth' ahead of it,
                 Laravel resolves the signed-in user against the connection active at
                 that moment, and every later auth()->user() lookup hits the tenant
                 database instead - returning a DIFFERENT row carrying the same
                 numeric id. That is why the header showed another user's name on
                 these module pages, and why the location dropdowns resolved wrongly.

                 app/Providers/RouteServiceProvider.php line 119 already has the
                 correct order; these module providers did not.

           IS-BASE: the stack below is now built from what the host application
           actually registers, instead of being hardcoded.

                 Every entry except 'web' and the module's own middleware is an
                 ALIAS owned by the host app. On a multi-tenant install they all
                 exist. On a single-base install without stancl/tenancy there is no
                 'tenant.context' alias, and a slimmer deployment may not define
                 'SetSessionData', 'language' or 'timezone' either.

                 Laravel does not treat an unknown alias as a no-op: it falls back
                 to reading the string as a CLASS NAME, then fails resolving
                 'tenant.context' out of the container. So one absent alias took
                 down every route in this module on a base system.

                 Filtering against the router's registered aliases keeps the exact
                 same order where the aliases exist, and simply omits the ones that
                 do not - which is what makes the module run unchanged on both
                 single-base and multi-tenant systems. */
        $aliases = $this->app['router']->getMiddleware();

        $optionalAliases = ['tenant.context', 'SetSessionData', 'language', 'timezone'];

        $webMiddleware = ['web'];

        // Ordered deliberately: tenant.context before auth (see LA-1152 above).
        if (isset($aliases['tenant.context'])) {
            $webMiddleware[] = 'tenant.context';
        }

        $webMiddleware[] = 'auth';

        foreach (['SetSessionData', 'language', 'timezone'] as $alias) {
            if (isset($aliases[$alias])) {
                $webMiddleware[] = $alias;
            }
        }

        $webMiddleware[] = EnsureExpensesNewSchema::class;

        $missing = array_values(array_filter(
            $optionalAliases,
            static fn (string $alias): bool => ! isset($aliases[$alias])
        ));

        if ($missing !== []) {
            // Logged rather than thrown: running without these is a supported
            // single-base deployment, but knowing which were skipped saves a long
            // hunt if something downstream expects them.
            Log::info('ExpensesNew: host middleware aliases not registered, skipped.', [
                'skipped' => $missing,
            ]);
        }

        // The API stack has the same problem, for the same reason.
        $apiMiddleware = ['api'];

        if (isset($aliases['tenant.context'])) {
            $apiMiddleware[] = 'tenant.context';
        }

        $apiMiddleware[] = EnsureExpensesNewSchema::class;

        $this->routes(function () use ($webMiddleware, $apiMiddleware): void {
            /*
             * IS-BASE: identify "already loaded" by this provider's own marker,
             * not by a route NAME anything else could own.
             *
             * The second condition used to be Route::has('expensesnew.dashboard').
             * Any other package registering that name - an older copy of this
             * module, a legacy expense module - made the guard conclude the routes
             * were present and skip the ENTIRE route file, so every /expenses-new
             * URL 404'd with nothing written to any log. The container marker below
             * is set only by this provider, so it cannot be claimed by anyone else.
             */
            if (! $this->app->bound('expensesnew.web_routes_loaded')) {
                $this->app->instance('expensesnew.web_routes_loaded', true);

                Route::middleware($webMiddleware)
                    ->prefix('expenses-new')
                    ->group(module_path('ExpensesNew', 'Routes/web.php'));

                // Compatibility URLs used by older sidebar/module registries.
                Route::middleware($webMiddleware)
                    ->get('/expense-manager', static function () {
                        return redirect()->route('expensesnew.dashboard');
                    })
                    ->name('expensesnew.compat.expense-manager');

                Route::middleware($webMiddleware)
                    ->get('/expenses-new-module', static function () {
                        return redirect()->route('expensesnew.dashboard');
                    })
                    ->name('expensesnew.compat.module');

                Route::middleware($webMiddleware)
                    ->get('/expensesnew', static function () {
                        return redirect()->route('expensesnew.dashboard');
                    })
                    ->name('expensesnew.compat.compact');
            }

            // IS-BASE: marker only, same reasoning as the web guard above.
            if (! $this->app->bound('expensesnew.api_routes_loaded')) {
                $this->app->instance('expensesnew.api_routes_loaded', true);

                Route::middleware($apiMiddleware)
                    ->prefix('api/expenses-new')
                    ->group(module_path('ExpensesNew', 'Routes/api.php'));
            }
        });
    }
}
