<?php

namespace Modules\PriceChangeNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\ScopeSessions;

/**
 * Uses the same tenant-domain strategy as the 9773 ProductsNew baseline.
 * It never changes a database name manually and never hardcodes a tenant.
 */
class InitializePriceChangeNewTenantContext
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->tenancyIsInitialized()) {
            return $this->continueRequest($request, $next);
        }

        if ($this->isCentralHost((string) $request->getHost())) {
            abort(404, 'Price Change New is available only inside a tenant business domain.');
        }

        return app(InitializeTenancyByDomain::class)->handle(
            $request,
            fn (Request $initializedRequest) => $this->continueRequest($initializedRequest, $next)
        );
    }

    private function continueRequest(Request $request, Closure $next)
    {
        if ($this->tenancyIsInitialized() && $request->hasSession()) {
            return app(ScopeSessions::class)->handle($request, $next);
        }

        return $next($request);
    }

    private function tenancyIsInitialized(): bool
    {
        try {
            return function_exists('tenancy') && (bool) tenancy()->initialized;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    private function isCentralHost(string $requestHost): bool
    {
        $requestHost = $this->normalizeHost($requestHost);
        if ($requestHost === null) {
            return false;
        }

        $configured = (array) config('tenancy.central_domains', []);
        $configured[] = config('app.url');
        $configured[] = env('CENTRAL_DOMAIN');
        $configured[] = env('APP_URL');
        $configured = array_merge(
            $configured,
            preg_split('/[\\s,;]+/', (string) env('CENTRAL_DOMAINS', ''), -1, PREG_SPLIT_NO_EMPTY) ?: []
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

        $candidate = str_contains($value, '://') ? $value : 'http://' . ltrim($value, '/');
        $host = parse_url($candidate, PHP_URL_HOST);
        $host = strtolower(trim((string) $host, ". \\t\\n\\r\\0\\x0B"));
        $host = preg_replace('/^www\\./', '', $host) ?: $host;

        return $host !== '' ? $host : null;
    }
}
