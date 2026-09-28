<?php

namespace Modules\StockTransferNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InitializeStockTransferTenantContext
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->tenancyIsInitialized() || $this->isCentralHost((string) $request->getHost())) {
            return $next($request);
        }

        $initializer = 'Stancl\\Tenancy\\Middleware\\InitializeTenancyByDomain';
        if (! class_exists($initializer)) {
            return $next($request);
        }

        return app($initializer)->handle(
            $request,
            fn (Request $initializedRequest) => $this->scopeSession($initializedRequest, $next)
        );
    }

    private function scopeSession(Request $request, Closure $next)
    {
        $scope = 'Stancl\\Tenancy\\Middleware\\ScopeSessions';
        if ($request->hasSession() && class_exists($scope)) {
            return app($scope)->handle($request, $next);
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
            preg_split('/[\s,;]+/', (string) env('CENTRAL_DOMAINS', ''), -1, PREG_SPLIT_NO_EMPTY) ?: []
        );

        foreach ($configured as $domain) {
            $host = $this->normalizeHost((string) $domain);
            if ($host !== null && hash_equals($host, $requestHost)) {
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
        $host = strtolower(trim((string) parse_url($candidate, PHP_URL_HOST), ". \t\n\r\0\x0B"));
        $host = preg_replace('/^www\./', '', $host) ?: $host;

        return $host !== '' ? $host : null;
    }
}
