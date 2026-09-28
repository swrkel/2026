<?php

namespace Modules\Ran\Http\Middleware;

use App\Utils\SidebarPermissionUtil;
use Closure;
use Illuminate\Http\Request;

class EnsureRanPageEnabled
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $businessId = (int) $request->session()->get('user.business_id');

        abort_unless(
            SidebarPermissionUtil::isEnabled($permission, $businessId),
            403,
            'This Ran page or action is disabled for the current business.'
        );

        return $next($request);
    }
}
