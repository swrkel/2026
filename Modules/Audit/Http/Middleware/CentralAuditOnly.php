<?php

namespace Modules\Audit\Http\Middleware;

use Closure;

class CentralAuditOnly
{
    public function handle($request, Closure $next)
    {
        try {
            if (function_exists('tenancy') && tenancy()->initialized) {
                abort(403, 'Central Audit is available only from the central system.');
            }
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
        }

        $host = strtolower((string) $request->getHost());
        $central = array_filter(array_map(function ($domain) {
            return strtolower(trim((string) $domain));
        }, (array) config('tenancy.central_domains', [])));

        if (!$central) {
            $fallback = parse_url((string) config('app.url'), PHP_URL_HOST);
            if ($fallback) {
                $central[] = strtolower($fallback);
            }
        }

        if ($central && !in_array($host, array_unique($central), true)) {
            abort(403, 'Central Audit is available only from a configured central domain.');
        }

        return $next($request);
    }
}
