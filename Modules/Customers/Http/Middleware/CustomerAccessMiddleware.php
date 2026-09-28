<?php

namespace Modules\Customers\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Customers\Services\CustomerPermissionService;

class CustomerAccessMiddleware
{
    protected CustomerPermissionService $permissionService;

    public function __construct(CustomerPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function handle(Request $request, Closure $next, string $ability = 'access')
    {
        $this->permissionService->authorize($ability);

        return $next($request);
    }
}
