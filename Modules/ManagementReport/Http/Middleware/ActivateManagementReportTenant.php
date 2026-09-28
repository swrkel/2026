<?php

namespace Modules\ManagementReport\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\ManagementReport\Support\TenantConnection;
use Symfony\Component\HttpFoundation\Response;

class ActivateManagementReportTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        TenantConnection::activate();

        return $next($request);
    }
}
