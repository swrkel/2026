<?php
namespace Modules\DealerManagement\Http\Middleware;

use Closure;
use Modules\DealerManagement\Services\HubContext;

class HubPermission
{
    public function handle($request, Closure $next, string $permission)
    {
        abort_unless(app(HubContext::class)->can($permission), 403, 'Unauthorized action.');
        return $next($request);
    }
}
