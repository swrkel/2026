<?php
namespace Modules\DealerManagement\Http\Middleware;

use Closure;
use Modules\DealerManagement\Services\DealerContext;

class DealerPermission
{
    public function handle($request, Closure $next, string $permission)
    {
        if (!app(DealerContext::class)->can($permission)) abort(403);
        return $next($request);
    }
}
