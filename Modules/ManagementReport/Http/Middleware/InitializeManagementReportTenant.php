<?php

namespace Modules\ManagementReport\Http\Middleware;

use App\Tenant;
use Closure;
use Illuminate\Http\Request;
use Modules\ManagementReport\Support\TenantConnection;
use RuntimeException;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

/**
 * Resolve Management Report database context for both dynamic tenants and
 * businesses that intentionally live in the central application's database.
 */
class InitializeManagementReportTenant
{
    public function handle(Request $request, Closure $next)
    {
        // Reset only this module's cached connection verification at the start
        // of every web request. This is harmless under PHP-FPM and prevents a
        // long-running worker from reusing the verification from another
        // business/request.
        TenantConnection::reset();

        if ($this->isInitialized()) {
            return $next($request);
        }

        // Important for central/shared-system businesses (including older
        // numbered deployments whose host is not present in central_domains):
        // if auth already succeeded and that business physically exists in the
        // current mysql DB, the request must stay there. Do this BEFORE Stancl
        // domain/business fallback so an unrelated/stale legacy tenant mapping
        // cannot move the report into another database and trigger a false 503.
        if (TenantConnection::isCentralBusinessDatabase()) {
            return $next($request);
        }

        // Copied tenant installations already point mysql at their exclusive
        // tenant database and intentionally have no Stancl domain row. Check
        // this before the domain middleware, which may return a 404 response
        // directly instead of throwing an exception that can be caught.
        if (TenantConnection::isStandaloneTenantDatabase()) {
            return $next($request);
        }

        try {
            return app(InitializeTenancyByDomain::class)->handle($request, $next);
        } catch (TenantCouldNotBeIdentifiedOnDomainException $exception) {
            // Older tenant installations can have a working business login
            // before their host was mirrored into Stancl's domains table.
        }

        $businessId = (int) (
            optional($request->user())->business_id
            ?: $request->session()->get('user.business_id')
            ?: $request->session()->get('business.id')
        );

        if ($businessId < 1) {
            throw new RuntimeException(
                'Management Report could not identify the authenticated business database.'
            );
        }

        $centralConnection = (string) config(
            'tenancy.database.central_connection',
            config('database.default', 'mysql')
        );

        $tenant = $this->findTenant(
            $centralConnection,
            $businessId,
            (string) $request->getHost()
        );

        if (!$tenant) {
            throw new RuntimeException(sprintf(
                'Management Report has no tenant database mapped to business %d.',
                $businessId
            ));
        }

        tenancy()->initialize($tenant);
        TenantConnection::reset();

        return $next($request);
    }

    private function findTenant(string $centralConnection, int $businessId, string $host): ?Tenant
    {
        $tenants = Tenant::on($centralConnection)->get();
        $activeDatabase = (string) config('database.connections.mysql.database');
        $prefix = (string) config('tenancy.database.prefix', '');
        $suffix = (string) config('tenancy.database.suffix', '');

        // The database selected for the authenticated business is the most
        // reliable identity on legacy installations where tenant JSON and
        // domain rows were never backfilled.
        if ($activeDatabase !== '') {
            $tenant = $tenants->first(function (Tenant $candidate) use ($activeDatabase, $prefix, $suffix) {
                $recordedDatabase = (string) data_get($candidate->data, 'tenancy_db_name');
                $conventionalDatabase = $prefix . $candidate->getTenantKey() . $suffix;

                return strcasecmp($recordedDatabase, $activeDatabase) === 0
                    || strcasecmp($conventionalDatabase, $activeDatabase) === 0;
            });

            if ($tenant) {
                return $tenant;
            }
        }

        $tenant = Tenant::on($centralConnection)
            ->where('data->business_id', $businessId)
            ->first();

        if ($tenant) {
            return $tenant;
        }

        // Some early tenant records used the deployment subdomain as the
        // tenant key but did not create the corresponding domains row.
        $subdomain = strtolower((string) strtok($host, '.'));
        $hostKeys = array_values(array_unique(array_filter([
            $subdomain,
            preg_replace('/^ep(?=\d)/', '', $subdomain),
        ])));

        return $tenants->first(
            static fn (Tenant $candidate) => in_array(
                strtolower((string) $candidate->getTenantKey()),
                $hostKeys,
                true
            )
        );
    }

    private function isInitialized(): bool
    {
        try {
            return function_exists('tenancy') && (bool) tenancy()->initialized;
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
