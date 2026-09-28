<?php

namespace Modules\EnterpriseFramework\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\EnterpriseFramework\Services\Security\EnterprisePermissionService;

class EnterpriseFrameworkPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission = 'enterprise_framework.view')
    {
        $service = app(EnterprisePermissionService::class);

        if (!$service->can($request->user(), $permission)) {
            abort(403, 'You do not have permission to access this Enterprise Framework resource.');
        }

        return $next($request);
    }
}
