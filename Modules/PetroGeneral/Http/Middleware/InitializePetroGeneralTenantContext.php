<?php

namespace Modules\PetroGeneral\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class InitializePetroGeneralTenantContext
{
    public function handle(Request $request, Closure $next)
    {
        try {
            if (function_exists('tenancy') && tenancy()->initialized) {
                return $next($request);
            }
        } catch (\Throwable $exception) {
        }

        $host = $this->normalizeHost((string) $request->getHost());

        if ($host !== null && $this->isCentralHost($host)) {
            return $next($request);
        }

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
            ?: optional(auth()->user())->business_id
            ?: 0
        );

        if ($businessId <= 0) {
            return false;
        }

        try {
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

        $candidate = str_contains($value, '://') ? $value : 'http://' . ltrim($value, '/');
        $host = parse_url($candidate, PHP_URL_HOST);
        $host = strtolower(trim((string) $host, ". \t\n\r\0\x0B"));
        $host = preg_replace('/^www\./', '', $host) ?: $host;

        return $host !== '' ? $host : null;
    }
}
