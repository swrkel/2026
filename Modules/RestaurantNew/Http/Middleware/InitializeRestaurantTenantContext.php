<?php
namespace Modules\RestaurantNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InitializeRestaurantTenantContext
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->tenancyIsInitialized()) return $next($request);
        if ($this->isCentralHost((string) $request->getHost())) {
            $request->attributes->set('restaurantnew.central_host', true);
            return $next($request);
        }
        $initializer = 'Stancl\Tenancy\Middleware\InitializeTenancyByDomain';
        if (!class_exists($initializer)) return $next($request);
        return app($initializer)->handle($request, function (Request $initialized) use ($next) {
            $scope = 'Stancl\Tenancy\Middleware\ScopeSessions';
            if ($initialized->hasSession() && class_exists($scope)) return app($scope)->handle($initialized, $next);
            return $next($initialized);
        });
    }
    private function tenancyIsInitialized(): bool
    {
        try { return function_exists('tenancy') && (bool) tenancy()->initialized; } catch (\Throwable) { return false; }
    }
    private function isCentralHost(string $requestHost): bool
    {
        $requestHost = $this->normaliseHost($requestHost);
        if (!$requestHost) return false;
        $configured = array_merge((array) config('tenancy.central_domains', []), [config('app.url'), env('CENTRAL_DOMAIN'), env('APP_URL')], preg_split('/[\s,;]+/', (string) env('CENTRAL_DOMAINS', ''), -1, PREG_SPLIT_NO_EMPTY) ?: []);
        foreach ($configured as $domain) {
            $host = $this->normaliseHost((string) $domain);
            if ($host && hash_equals($host, $requestHost)) return true;
        }
        return false;
    }
    private function normaliseHost(string $value): ?string
    {
        $value = trim($value); if ($value === '') return null;
        $candidate = str_contains($value, '://') ? $value : 'http://' . ltrim($value, '/');
        $host = strtolower(trim((string) parse_url($candidate, PHP_URL_HOST), ". \t\n\r\0\x0B"));
        $host = preg_replace('/^www\./', '', $host) ?: $host;
        return $host !== '' ? $host : null;
    }
}
