<?php

namespace Modules\PetroPDNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InitializePdnewTenantContext
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->initialized() || $this->isCentralHost((string) $request->getHost())) {
            return $next($request);
        }

        $initializer = 'Stancl\\Tenancy\\Middleware\\InitializeTenancyByDomain';
        if (! class_exists($initializer)) {
            return $next($request);
        }

        return app($initializer)->handle($request, function (Request $initializedRequest) use ($next) {
            $scopeSessions = 'Stancl\\Tenancy\\Middleware\\ScopeSessions';
            if ($initializedRequest->hasSession() && class_exists($scopeSessions)) {
                return app($scopeSessions)->handle($initializedRequest, $next);
            }

            return $next($initializedRequest);
        });
    }

    private function initialized(): bool
    {
        try {
            return function_exists('tenancy') && (bool) tenancy()->initialized;
        } catch (\Throwable) {
            return false;
        }
    }

    private function isCentralHost(string $requestHost): bool
    {
        $requestHost = $this->normaliseHost($requestHost);
        if ($requestHost === null) return false;

        $configured = array_merge(
            (array) config('tenancy.central_domains', []),
            [config('app.url'), env('CENTRAL_DOMAIN'), env('APP_URL')],
            preg_split('/[\s,;]+/', (string) env('CENTRAL_DOMAINS', ''), -1, PREG_SPLIT_NO_EMPTY) ?: []
        );

        foreach ($configured as $domain) {
            $host = $this->normaliseHost((string) $domain);
            if ($host !== null && hash_equals($host, $requestHost)) return true;
        }

        return false;
    }

    private function normaliseHost(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;
        $candidate = str_contains($value, '://') ? $value : 'http://' . ltrim($value, '/');
        $host = strtolower(trim((string) parse_url($candidate, PHP_URL_HOST), ". \t\n\r\0\x0B"));
        $host = preg_replace('/^www\./', '', $host) ?: $host;
        return $host !== '' ? $host : null;
    }
}
