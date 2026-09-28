<?php

namespace Modules\Product\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Product\Utils\ProductPermissionUtil;

class EnsureProductPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        app(ProductPermissionUtil::class)->abortUnless($permission);
        return $next($request);
    }
}
