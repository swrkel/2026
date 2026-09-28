<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;

class TenancyServiceProvider extends ServiceProvider
{
    public function events()
    {
        return [
            // Tenant events
            Events\CreatingTenant::class => [],
            Events\TenantCreated::class => [
                JobPipeline::make([
                    // Jobs\CreateDatabase::class,
                    // Jobs\MigrateDatabase::class,
                    // Jobs\SeedDatabase::class,

                    // Your own jobs to prepare the tenant.
                    // Provision API keys, create S3 buckets, anything you want!

                ])->send(function (Events\TenantCreated $event) {
                    return $event->tenant;
                })->shouldBeQueued(false), // `false` by default, but you probably want to make this `true` for production.
            ],
            Events\SavingTenant::class => [],
            Events\TenantSaved::class => [],
            Events\UpdatingTenant::class => [],
            Events\TenantUpdated::class => [],
            Events\DeletingTenant::class => [],
            Events\TenantDeleted::class => [
                JobPipeline::make([
                    // Jobs\DeleteDatabase::class,
                ])->send(function (Events\TenantDeleted $event) {
                    return $event->tenant;
                })->shouldBeQueued(false), // `false` by default, but you probably want to make this `true` for production.
            ],

            // Domain events
            Events\CreatingDomain::class => [],
            Events\DomainCreated::class => [],
            Events\SavingDomain::class => [],
            Events\DomainSaved::class => [],
            Events\UpdatingDomain::class => [],
            Events\DomainUpdated::class => [],
            Events\DeletingDomain::class => [],
            Events\DomainDeleted::class => [],

            // Database events
            Events\DatabaseCreated::class => [],
            Events\DatabaseMigrated::class => [],
            Events\DatabaseSeeded::class => [],
            Events\DatabaseRolledBack::class => [],
            Events\DatabaseDeleted::class => [],

            // Tenancy events
            Events\InitializingTenancy::class => [],
            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class,
            ],

            Events\EndingTenancy::class => [],
            Events\TenancyEnded::class => [
                Listeners\RevertToCentralContext::class,
            ],

            Events\BootstrappingTenancy::class => [],
            Events\TenancyBootstrapped::class => [
                function ($event) {
                    $tenantId = tenant('id');
                    $prefix = env('TENANT_DATABASE_PREFIX', 'nivasa_');
                    $database = $prefix . $tenantId;

                    $currentDatabase = (string) config('database.connections.mysql.database');

                    if ($currentDatabase !== $database) {
                        // Close the previous central/tenant handle before changing
                        // the connection configuration. Do not reconnect eagerly:
                        // Laravel will open the tenant connection only when the
                        // request actually performs its first query.
                        \DB::disconnect('mysql');

                        config([
                            'database.connections.mysql.database' => $database,
                        ]);
                    }
                },
            ],

            Events\RevertingToCentralContext::class => [],
            Events\RevertedToCentralContext::class => [],

            // Resource syncing
            Events\SyncedResourceSaved::class => [
                Listeners\UpdateSyncedResource::class,
            ],

            // Fired only when a synced resource is changed in a different DB than the origin DB (to avoid infinite loops)
            Events\SyncedResourceChangedInForeignDatabase::class => [],
        ];
    }

    public function register()
    {
        //
    }

    public function boot()
    {
        // Allow direct-database tenant roots (APP_URL host, not central, no
        // domains mapping) to continue through Stancl's domain middleware using
        // the database that is already active for this deployment.
        $this->configureDirectDatabaseTenantIdentificationFallback();

        // The same codebase can run from a central root or a copied tenant root.
        // Before routes are mapped, let the canonical central `domains` table
        // decide whether the CURRENT hostname belongs to a tenant. A tenant
        // mapping always wins over stale/copied CENTRAL_DOMAIN or APP_URL values.
        $this->reconcileCurrentHostWithTenantDomains();

        $this->bootEvents();
        $this->mapRoutes();

        // Copied tenant roots can have a stale/partial Nwidart module registry.
        // Normal module discovery remains the primary path; this is only a
        // fail-safe when the canonical GET /suppliers route is still absent.
        $this->mapStandaloneSuppliersRoutes();

        // Finance > List Accounts is tenant-owned. Register the standalone
        // Finance route from the provider that actually boots tenant routes.
        // RouteServiceProvider alone is not sufficient on this application
        // because tenant route registration is handled separately here.
        $this->mapStandaloneFinanceAccountRoutes();

        $this->makeTenancyMiddlewareHighestPriority();
    }

    protected function bootEvents()
    {
        foreach ($this->events() as $event => $listeners) {
            foreach (array_unique($listeners) as $listener) {
                if ($listener instanceof JobPipeline) {
                    $listener = $listener->toListener();
                }

                Event::listen($event, $listener);
            }
        }
    }

    protected function mapRoutes()
    {
        $tenantRoutes = base_path('routes/tenant.php');

        if (! file_exists($tenantRoutes)) {
            return;
        }

        /*
         * Central and tenant route files still contain a large legacy overlap.
         * On a central HTTP request, registering routes/tenant.php after
         * routes/web.php can make the tenant copy of the same URI win and
         * PreventAccessFromCentralDomains then returns a false 404.
         *
         * Do not bind all web routes to the central domain because some legacy
         * tenant pages still live only in routes/web.php. Instead, keep web.php
         * host-neutral and simply skip the tenant route collection on a known
         * central HTTP host. Tenant requests continue loading both collections,
         * preserving every legacy tenant URL. Console commands still load both
         * collections so route inspection remains complete.
         */
        if ($this->isCentralHttpRequest()) {
            return;
        }

        Route::namespace('App\\Http\\Controllers')
            ->group($tenantRoutes);
    }

    /**
     * Reconcile the current HTTP hostname with the canonical central domains
     * mapping before route registration and before Stancl's domain middleware
     * executes.
     *
     * Rules:
     *  - If the current hostname exists in the central `domains` table, it is a
     *    TENANT host. Remove it from tenancy.central_domains for this request.
     *  - If it is not tenant-mapped, keep the configured central-domain list as
     *    is. A genuine central host therefore remains central.
     *  - Console commands do not have an authoritative request hostname, so the
     *    configured list is left untouched.
     *
     * This makes copied application roots safe without requiring APP_URL to be
     * treated as a central-domain declaration.
     */
    private function reconcileCurrentHostWithTenantDomains(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $host = strtolower(trim((string) request()->getHost(), ". \t\n\r\0\x0B"));
        if ($host === '' || in_array($host, ['localhost', '127.0.0.1'], true)) {
            return;
        }

        try {
            $centralConnection = (string) config(
                'tenancy.database.central_connection',
                config('database.default')
            );

            if (! \Illuminate\Support\Facades\Schema::connection($centralConnection)->hasTable('domains')) {
                return;
            }

            $tenantId = \Illuminate\Support\Facades\DB::connection($centralConnection)
                ->table('domains')
                ->whereRaw('LOWER(domain) = ?', [$host])
                ->value('tenant_id');

            if (empty($tenantId)) {
                return;
            }

            $normalize = static function ($value): ?string {
                $value = trim((string) $value);
                if ($value === '') {
                    return null;
                }

                $candidate = strpos($value, '://') !== false
                    ? $value
                    : 'http://' . ltrim($value, '/');
                $normalizedHost = parse_url($candidate, PHP_URL_HOST);
                $normalizedHost = strtolower(trim((string) $normalizedHost, ". \t\n\r\0\x0B"));

                return $normalizedHost !== '' ? $normalizedHost : null;
            };

            $centralDomains = [];
            foreach ((array) config('tenancy.central_domains', []) as $domain) {
                $normalized = $normalize($domain);
                if ($normalized !== null && $normalized !== $host) {
                    $centralDomains[] = $normalized;
                }
            }

            // Keep local development entries stable even if the source config
            // was malformed or duplicated.
            $centralDomains[] = '127.0.0.1';
            $centralDomains[] = 'localhost';

            config([
                'tenancy.central_domains' => array_values(array_unique($centralDomains)),
            ]);

            \Illuminate\Support\Facades\Log::info(
                'Tenancy host resolver: tenant mapping overrides central-domain configuration.',
                [
                    'host' => $host,
                    'tenant_id' => (string) $tenantId,
                    'central_domains' => config('tenancy.central_domains', []),
                ]
            );
        } catch (\Throwable $e) {
            // Fail closed to the existing configuration. A resolver lookup must
            // never take the whole application down.
            \Illuminate\Support\Facades\Log::warning(
                'Tenancy host resolver could not verify current host against central domains table.',
                [
                    'host' => $host,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Stancl's InitializeTenancyByDomain normally throws when no domains row is
     * found. That is correct for mapped multi-database tenants, but direct-copy
     * deployments can intentionally have APP_URL pointing at a tenant/business
     * database with no domains row and no database switch required.
     *
     * Only the deployment's own APP_URL host may use this fallback, and never a
     * configured central domain. Unknown hosts keep Stancl's normal failure.
     */
    private function configureDirectDatabaseTenantIdentificationFallback(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        Middleware\InitializeTenancyByDomain::$onFail = function ($exception, $request, $next) {
            $host = strtolower(trim((string) $request->getHost(), ". \t\n\r\0\x0B"));

            if ($this->isDirectDatabaseTenantHost($host)) {
                \Illuminate\Support\Facades\Log::info(
                    'Tenancy domain middleware: continuing in direct-database tenant mode.',
                    [
                        'host' => $host,
                        'database' => \Illuminate\Support\Facades\DB::connection()->getDatabaseName(),
                    ]
                );

                return $next($request);
            }

            throw $exception;
        };
    }

    private function isDirectDatabaseTenantHost(string $host): bool
    {
        $normalize = static function ($value): ?string {
            $value = trim((string) $value);
            if ($value === '') {
                return null;
            }

            $candidate = strpos($value, '://') !== false
                ? $value
                : 'http://' . ltrim($value, '/');
            $normalizedHost = parse_url($candidate, PHP_URL_HOST);
            $normalizedHost = strtolower(trim((string) $normalizedHost, ". \t\n\r\0\x0B"));

            return $normalizedHost !== '' ? $normalizedHost : null;
        };

        $host = strtolower(trim($host, ". \t\n\r\0\x0B"));
        $appHost = $normalize(config('app.url'));

        if ($host === '' || $appHost === null || $host !== $appHost) {
            return false;
        }

        $centralDomains = [];
        foreach ((array) config('tenancy.central_domains', []) as $domain) {
            $normalized = $normalize($domain);
            if ($normalized !== null) {
                $centralDomains[] = $normalized;
            }
        }

        return ! in_array($host, array_values(array_unique($centralDomains)), true);
    }

    private function isCentralHttpRequest(): bool
    {
        if ($this->app->runningInConsole()) {
            return false;
        }

        $normalize = static function ($value): ?string {
            $value = trim((string) $value);
            if ($value === '') {
                return null;
            }

            $candidate = strpos($value, '://') !== false
                ? $value
                : 'http://' . ltrim($value, '/');
            $host = parse_url($candidate, PHP_URL_HOST);
            $host = strtolower(trim((string) $host, ". \t\n\r\0\x0B"));

            return $host !== '' ? preg_replace('/^www\./', '', $host) : null;
        };

        $host = $normalize((string) request()->getHost());
        if ($host === null) {
            return false;
        }

        /*
         | config('tenancy.central_domains') is the whole list.
         |
         | config/tenancy.php already reads CENTRAL_DOMAIN, APP_URL and
         | CENTRAL_DOMAINS, normalises them, and appends 127.0.0.1 and
         | localhost - so the env() calls that used to be here were doing the
         | same work twice.
         |
         | Worse, they made `php artisan config:cache` unsafe: env() returns
         | null once config is cached, so CENTRAL_DOMAIN and APP_URL would have
         | vanished from this list and nivasa.shop would have been treated as a
         | TENANT. That is the same failure that had Super Admin saves landing
         | in the wrong database.
         |
         | Reading only from config() keeps this correct cached or not, and
         | lets config caching be enabled - measured at ~2.7s of server time
         | per page without it.
         */
        $configured = (array) config('tenancy.central_domains', []);

        $centralDomains = [];
        foreach ($configured as $domain) {
            $normalized = $normalize($domain);
            if ($normalized !== null) {
                $centralDomains[] = $normalized;
            }
        }

        return in_array($host, array_values(array_unique($centralDomains)), true);
    }

    /**
     * Register the standalone Suppliers module as a tenant-route fail-safe.
     *
     * Nwidart module discovery remains authoritative. This method only runs
     * when GET /suppliers is genuinely missing, which makes copied application
     * roots resilient to stale/partial module discovery without duplicating
     * routes on healthy deployments.
     */
    protected function mapStandaloneSuppliersRoutes(): void
    {
        // Do not expose tenant-owned Supplier pages on a true central host.
        if ($this->isCentralHttpRequest()) {
            return;
        }

        foreach (Route::getRoutes() as $route) {
            if (trim((string) $route->uri(), '/') === 'suppliers'
                && in_array('GET', $route->methods(), true)) {
                return;
            }
        }

        $modulePath = base_path('Modules/Suppliers');
        $routeFile = $modulePath . '/Routes/web.php';
        $viewPath = $modulePath . '/Resources/views';
        $langPath = $modulePath . '/Resources/lang';

        if (! is_file($routeFile)) {
            \Illuminate\Support\Facades\Log::warning(
                'Suppliers tenant-route fallback skipped because route file is missing.',
                ['route_file' => $routeFile]
            );
            return;
        }

        if (is_dir($viewPath)) {
            \Illuminate\Support\Facades\View::addNamespace('suppliers', $viewPath);
        }

        if (is_dir($langPath)) {
            app('translator')->addNamespace('suppliers', $langPath);
        }

        require $routeFile;
    }

    /**
     * Register Finance > List Accounts on the tenant route boot path.
     *
     * The main RouteServiceProvider can be bypassed/overridden by this
     * application's separate tenancy route lifecycle. Registering here makes
     * /finance/account visible in `php artisan route:list` and available on
     * tenant domains. The `web` group already enforces CheckRoutePermission and
     * EnforceBusinessSidebarModuleAccess, so no permission layer is bypassed.
     */
    protected function mapStandaloneFinanceAccountRoutes(): void
    {
        // Never expose tenant Finance pages on a known central HTTP domain.
        // Console route inspection intentionally continues so route:list sees it.
        if ($this->isCentralHttpRequest()) {
            return;
        }

        if (Route::has('finance.account.index')) {
            return;
        }

        $modulePath = base_path('Modules/Finance');
        $routeFile = $modulePath . '/Routes/standalone_accounts.php';
        $controllerFile = $modulePath . '/Http/Controllers/StandaloneAccountController.php';
        $viewPath = $modulePath . '/Resources/views';

        if (! is_file($routeFile) || ! is_file($controllerFile)) {
            \Log::error('Finance standalone List Accounts files are missing.', [
                'route_file' => $routeFile,
                'controller_file' => $controllerFile,
            ]);
            return;
        }

        $controllerClass = \Modules\Finance\Http\Controllers\StandaloneAccountController::class;
        if (! class_exists($controllerClass, false)) {
            require_once $controllerFile;
        }

        if (! class_exists($controllerClass, false)) {
            \Log::error('Finance standalone List Accounts controller could not be loaded from tenant provider.', [
                'controller_file' => $controllerFile,
            ]);
            return;
        }

        if (is_dir($viewPath)) {
            \Illuminate\Support\Facades\View::addNamespace('finance', $viewPath);
        }

        Route::middleware([
            'web',
            \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
            \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
            \Stancl\Tenancy\Middleware\ScopeSessions::class,
            'IsInstalled',
            'auth:customer,web',
            'SetSessionData',
            'DayEnd',
            'language',
            'timezone',
            'bootstrap',
            'isVerified',
            'tenant.context',
        ])->group($routeFile);
    }

    protected function makeTenancyMiddlewareHighestPriority()
    {
        $tenancyMiddleware = [
            // Even higher priority than the initialization middleware
            Middleware\PreventAccessFromCentralDomains::class,

            Middleware\InitializeTenancyByDomain::class,
            Middleware\InitializeTenancyBySubdomain::class,
            Middleware\InitializeTenancyByDomainOrSubdomain::class,
            Middleware\InitializeTenancyByPath::class,
            Middleware\InitializeTenancyByRequestData::class,
        ];

        foreach (array_reverse($tenancyMiddleware) as $middleware) {
            $this->app[\Illuminate\Contracts\Http\Kernel::class]->prependToMiddlewarePriority($middleware);
        }
    }
}
