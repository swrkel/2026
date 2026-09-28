<?php

namespace Modules\Customers\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Customers\Services\CustomerPermissionService;

class EnsureCustomersAccess
{
    protected CustomerPermissionService $permissionService;

    public function __construct(CustomerPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Block Customers module direct URLs unless both package subscription and role permission allow access.
     */
    public function handle(Request $request, Closure $next, string $ability = 'access')
    {
        $this->permissionService->authorize($ability);

        return $next($request);
    }
}
