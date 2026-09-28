<?php

namespace Modules\Suppliers\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Suppliers\Utils\SupplierPermissionUtil;

class EnsureSuppliersModuleAccess
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(
            SupplierPermissionUtil::isEnabled() && SupplierPermissionUtil::userCanAccess(),
            403,
            'The standalone Suppliers Module is not enabled for this business or user.'
        );

        return $next($request);
    }
}
