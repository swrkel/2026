<?php

namespace Modules\Finance\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

/**
 * Initializes the correct database context for Finance routes.
 *
 * Supported deployments:
 *  - a real Stancl tenant/custom domain -> initialize that tenant database;
 *  - a configured central host -> keep the master/system database;
 *  - a master/system-database business served from another business host ->
 *    keep the master/system database when the authenticated business session
 *    is valid and no Stancl tenant can be resolved for that host.
 *
 * The last case is important for mixed installations where some businesses
 * have their own tenant database while older/central businesses still use the
 * master database. A missing tenant-domain record must not turn a valid logged
 * in master business Finance page into HTTP 404.
 */
class InitializeFinanceTenantContext
{
    public function handle(Request $request, Closure $next)
    {
        try {
            if (function_exists('tenancy') && tenancy()->initialized) {
                return $next($request);
            }
        } catch (\Throwable $exception) {
            // Continue with host resolution. A missing/unbooted tenancy helper
            // must not make a master/system Finance page return 404.
        }

        $host = $this->normalizeHost((string) $request->getHost());

        if ($host !== null && $this->isCentralHost($host)) {
            return $next($request);
        }

        /*
         * Prefer Stancl's canonical resolver for any non-central host.  A real
         * tenant domain therefore keeps exactly the normal tenant behaviour.
         *
         * When Stancl says "tenant not found", do not blindly convert the
         * request into 404.  This ERP also has businesses that intentionally
         * live in the master/system DB and may be reached through a business
         * host which is not present in tenancy.domains.  If the current web
         * session points at a real business in the current (master) DB, that is
         * a valid Finance context and the request should continue there.
         */
        try {
            return app(InitializeTenancyByDomain::class)->handle($request, $next);
        } catch (\Throwable $exception) {
            if ($this->isTenantNotFoundException($exception) && $this->hasValidMasterBusinessContext($request)) {
                return $next($request);
            }

            throw $exception;
        }
    }

    private function hasValidMasterBusinessContext(Request $request): bool
    {
        $businessId = (int) (
            $request->session()->get('user.business_id')
            ?: $request->session()->get('business.id')
            ?: 0
        );

        if ($businessId <= 0) {
            return false;
        }

        try {
            // Before tenant initialization the default connection is the
            // system/master connection. Verify that the logged-in business is
            // genuinely present there before allowing the master fallback.
            if (! Schema::hasTable('business')) {
                return false;
            }

            return DB::table('business')->where('id', $businessId)->exists();
        } catch (\Throwable $exception) {
            return false;
        }
    }

    private function isTenantNotFoundException(\Throwable $exception): bool
    {
        $class = strtolower(get_class($exception));
        $message = strtolower((string) $exception->getMessage());

        if (str_contains($class, 'tenantcouldnotbeidentified')) {
            return true;
        }

        return str_contains($message, 'tenant could not be identified')
            || str_contains($message, 'tenant could not be identified on domain')
            || str_contains($message, 'could not identify tenant');
    }

    private function isCentralHost(string $requestHost): bool
    {
        $requestHost = $this->normalizeHost($requestHost);
        if ($requestHost === null) {
            return false;
        }

        $configured = (array) config('tenancy.central_domains', []);
        $configured[] = env('CENTRAL_DOMAIN');
        $configured[] = env('APP_URL');
        $configured = array_merge(
            $configured,
            preg_split('/[\s,;]+/', (string) env('CENTRAL_DOMAINS', ''), -1, PREG_SPLIT_NO_EMPTY) ?: []
        );

        foreach ($configured as $domain) {
            $centralHost = $this->normalizeHost((string) $domain);
            if ($centralHost !== null && hash_equals($centralHost, $requestHost)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeHost(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $candidate = str_contains($value, '://')
            ? $value
            : 'http://' . ltrim($value, '/');

        $host = parse_url($candidate, PHP_URL_HOST);
        $host = strtolower(trim((string) $host, ". \t\n\r\0\x0B"));
        $host = preg_replace('/^www\./', '', $host) ?: $host;

        return $host !== '' ? $host : null;
    }
}
