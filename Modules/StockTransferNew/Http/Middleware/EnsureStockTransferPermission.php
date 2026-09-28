<?php

namespace Modules\StockTransferNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\StockTransferNew\Services\StockTransferPermissionService;

class EnsureStockTransferPermission
{
    public function __construct(protected StockTransferPermissionService $permissions) {}

    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!$this->permissions->can($request->user(), $permission)) {
            abort(403, 'You do not have permission to access this Stock Transfer-New function.');
        }
        return $next($request);
    }
}
