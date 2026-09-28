<?php

namespace App\Http\Middleware;

use App\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenancy = tenancy();

        // Stancl Tenancy v3 exposes initialization state as a property.
        if ($tenancy->initialized) {
            return $next($request);
        }

        $host = strtolower($request->getHost());
        $centralDomains = array_map('strtolower', config('tenancy.central_domains', []));

        if (in_array($host, $centralDomains, true)) {
            return $next($request);
        }

        /*
         |----------------------------------------------------------------------
         | Resolve the tenant by DOMAIN first, then by subdomain-as-id.
         |----------------------------------------------------------------------
         |
         | This used to do only:
         |
         |     $subdomain = explode('.', $host, 2)[0];
         |     $tenant = Tenant::query()->find($subdomain);
         |
         | i.e. it assumed the subdomain IS the tenant's primary key. That holds
         | for tenants whose id happens to match their subdomain, and fails for
         | every other one - which is why
         | ep163-madawalaentprises.syzeasy.online returned a bare
         | "404 NOT FOUND" on /expenses-new while the nivasa.shop tenants were
         | fine. It was not an Expenses New fault at all: `tenant.context` runs
         | on many modules, so that host could not reach any of them.
         |
         | The application already stores hostnames properly - there is a
         | `domains` table mapping `domain` to `tenant_id`, and config/tenancy.php
         | registers a domain_model. That is the canonical Stancl lookup and it is
         | now tried first, on the FULL host.
         |
         | The old subdomain-as-id lookup is kept as a fallback so every tenant
         | that resolves today carries on resolving.
         */
        $tenant = null;

        try {
            /*
             |------------------------------------------------------------------
             | Two corrections to the domain lookup I added earlier.
             |------------------------------------------------------------------
             |
             | 1. EXPLICIT CENTRAL CONNECTION.
             |    This used DB::table('domains'), which runs on the DEFAULT
             |    connection. `domains` and `tenants` live in the CENTRAL
             |    database, and the default connection is not guaranteed to be
             |    central at this point. Reading them from a tenant connection
             |    can resolve the wrong tenant - and because
             |    Superadmin's All Business page has no tenant scoping of its own
             |    (`Business::query()` trusts whatever connection is active), the
             |    result is a business list from the wrong database.
             |
             | 2. FULL HOST ONLY.
             |    It also fell back to matching `domains.domain` against the bare
             |    SUBDOMAIN. That is not a unique key - two hosts on different
             |    top-level domains can share a subdomain - so it could match a
             |    row belonging to a different tenant. The original code matched
             |    Tenant::find($subdomain), which is a primary key and unique.
             |    That fallback is kept below, unchanged; only the loose match
             |    against `domains` is gone.
             */
            $centralConnection = config('tenancy.database.central_connection', config('database.default'));

            if (Schema::connection($centralConnection)->hasTable('domains')) {
                $tenantId = DB::connection($centralConnection)
                    ->table('domains')
                    ->whereRaw('LOWER(domain) = ?', [$host])
                    ->value('tenant_id');

                if (! empty($tenantId)) {
                    $tenant = Tenant::query()->find($tenantId);
                }
            }
        } catch (\Throwable $e) {
            // Fall through to the subdomain lookup below.
            Log::warning('SetTenantContext: domains lookup failed; falling back to the subdomain.', [
                'host' => $host,
                'message' => $e->getMessage(),
            ]);
        }

        $subdomain = explode('.', $host, 2)[0] ?? '';

        if (! $tenant && $subdomain !== '') {
            $tenant = Tenant::query()->find($subdomain);
        }

        if (! $tenant) {
            /*
             |------------------------------------------------------------------
             | DIRECT-DATABASE TENANT ROOT FALLBACK
             |------------------------------------------------------------------
             |
             | Some deployments intentionally use the SAME database as both the
             | central/default connection and the tenant/business database. In
             | that mode there is no domains row and no database switch to make.
             |
             | We only allow this fallback when ALL of the following are true:
             |   - the request host is NOT a configured central domain;
             |   - the request host exactly matches this deployment's APP_URL;
             |   - normal domain/subdomain tenant resolution found nothing.
             |
             | This keeps unknown Host headers and unrelated domains blocked,
             | while allowing copied/direct roots such as 127copy to use the
             | database they are already connected to.
             */
            if ($this->isDirectDatabaseTenantHost($host)) {
                $this->scopePermissionCacheToCurrentDatabase($host);

                Log::info('SetTenantContext: using direct-database tenant context.', [
                    'host' => $host,
                    'database' => DB::connection()->getDatabaseName(),
                ]);

                return $next($request);
            }

            Log::warning('SetTenantContext: no tenant matched this host.', [
                'host' => $host,
                'subdomain' => $subdomain,
                'url' => $request->fullUrl(),
            ]);

            abort(404, 'Tenant could not be resolved for host: ' . $host);
        }

        $tenancy->initialize($tenant);

        /*
         |----------------------------------------------------------------------
         | S640 / LA-1151: the permission cache is shared between tenants.
         |----------------------------------------------------------------------
         |
         | config/tenancy.php enables DatabaseTenancyBootstrapper (so queries go
         | to the right database) but CacheTenancyBootstrapper is COMMENTED OUT,
         | and config/cache.php defaults to the `file` store. Nothing scopes the
         | cache per tenant.
         |
         | spatie/laravel-permission caches the entire permission and role set
         | under ONE global key for 24 hours. With the cache unscoped, whichever
         | tenant populates it first serves its permissions to every other
         | tenant, until it expires or something flushes it.
         |
         | That is why a role can be saved correctly - the rows are in the right
         | database - and the user still resolves no permissions: can() answers
         | from another tenant's cached set. It also explains why three testers
         | all reproduced it and why fixes to the role screens changed nothing.
         |
         | TWO STEPS, both cheap:
         |
         |   1. Give the cache a per-tenant key. Newer spatie versions read
         |      permission.cache.key; on older ones this is simply ignored, which
         |      is why step 2 is here as well.
         |
         |   2. Forget the cached permissions now that the tenant connection is
         |      active, so the set is rebuilt from THIS tenant's database. Costs
         |      one query per request and is correct on every version.
         |
         | The proper long-term fix is to enable CacheTenancyBootstrapper, but
         | that changes caching behaviour application-wide and wants its own
         | testing window. This is scoped to the permission cache, which is where
         | the damage is.
         */
        try {
            $tenantKey = (string) $tenant->getTenantKey();

            if (config('permission.cache.key') !== null) {
                config(['permission.cache.key' => 'spatie.permission.cache.tenant.' . $tenantKey]);
            }

            if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            }
        } catch (\Throwable $e) {
            // Never let cache scoping break the request; a stale cache is bad,
            // a dead tenant page is worse.
            \Illuminate\Support\Facades\Log::warning('SetTenantContext: could not scope the permission cache.', [
                'tenant' => $tenant->getTenantKey(),
                'message' => $e->getMessage(),
            ]);
        }

        /*
         | IS2115: forget the user resolved BEFORE this middleware ran.
         |
         | The user is read while the default connection still points at the
         | CENTRAL database, and only then does tenancy repoint it at the
         | tenant. The resolved user object is never re-read, so for the rest of
         | the request auth()->user() is CENTRAL's row for that id - a different
         | person, on a different business.
         |
         | On nivasa_ishadi-pd, user 11 is dinushika-02 (business 2). Centrally,
         | user 11 is Products2 (business 5). She was served Products2's
         | identity, and SetSessionData then called Business::findOrFail(5)
         | against a tenant that has only businesses 1 and 2 - a missing model,
         | which Laravel renders as a silent 404 with nothing in the log.
         |
         | That it surfaced as a 404 was luck. Had the tenant HAD a business 5,
         | she would have been working quietly in the wrong one.
         |
         | This is the same class of problem the permission-cache scoping above
         | addresses: something read before the connection switch is stale
         | afterwards. Forgetting it here means the next auth()->user() reads
         | from the tenant, which is the only correct source on a tenant domain.
        */
        try {
            if (auth()->hasUser()) {
                auth()->forgetUser();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                'SetTenantContext: could not re-resolve the user for this tenant.',
                ['message' => $e->getMessage()]
            );
        }

        return $next($request);
    }
    /**
     * Whether this request is the deployment's own direct-database tenant host.
     *
     * A host is never treated as direct-tenant when it is configured as central.
     * Matching APP_URL is required so arbitrary Host headers cannot bypass tenant
     * identification.
     */
    private function isDirectDatabaseTenantHost(string $host): bool
    {
        $normalize = static function ($value): ?string {
            $value = trim((string) $value);
            if ($value === '') {
                return null;
            }

            $candidate = str_contains($value, '://')
                ? $value
                : 'http://' . ltrim($value, '/');

            $normalized = parse_url($candidate, PHP_URL_HOST);
            $normalized = strtolower(trim((string) $normalized, ". \t\n\r\0\x0B"));

            return $normalized !== '' ? $normalized : null;
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

    /**
     * Keep Spatie permission cache isolated even when Stancl initialization is
     * intentionally skipped because this deployment already points at its tenant
     * database.
     */
    private function scopePermissionCacheToCurrentDatabase(string $host): void
    {
        try {
            $database = (string) DB::connection()->getDatabaseName();
            $scope = $database !== '' ? $database : $host;
            $scope = preg_replace('/[^a-zA-Z0-9_.-]+/', '_', $scope) ?: 'direct';

            if (config('permission.cache.key') !== null) {
                config(['permission.cache.key' => 'spatie.permission.cache.db.' . $scope]);
            }

            if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            }
        } catch (\Throwable $e) {
            Log::warning('SetTenantContext: could not scope direct-database permission cache.', [
                'host' => $host,
                'message' => $e->getMessage(),
            ]);
        }
    }

}
