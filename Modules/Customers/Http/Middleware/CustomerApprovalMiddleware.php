<?php

namespace Modules\Customers\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Customers\Services\CustomerPermissionService;

class CustomerApprovalMiddleware
{
    protected CustomerPermissionService $permissionService;

    public function __construct(CustomerPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function handle(Request $request, Closure $next)
    {
        $this->permissionService->authorize('approve');

        return $next($request);
    }
}
