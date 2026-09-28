<?php

namespace Modules\CommunicationHub\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Initializes the ERP tenant database without imposing a domain-only tenancy
 * restriction. The host ERP may resolve tenants by login/session instead of by
 * Stancl tenant domains, so this middleware must never turn a valid authenticated
 * business request into a 404 solely because the current host is a central domain.
 */
class InitializeCommunicationHubTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (function_exists('tenant_db')) {
                tenant_db();
            }
        } catch (\Throwable $e) {
            // Controllers use TenantConnection guards and can show setup guidance.
            // Keep the page reachable instead of converting tenant resolution into 404.
            Log::warning('Communication Hub tenant initialization fallback used', [
                'host' => $request->getHost(),
                'path' => $request->path(),
                'error' => $e->getMessage(),
            ]);
        }

        return $next($request);
    }
}
