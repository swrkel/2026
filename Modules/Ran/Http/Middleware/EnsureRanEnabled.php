<?php

namespace Modules\Ran\Http\Middleware;

use App\Utils\SidebarPermissionUtil;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRanEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $businessId = (int) (session('user.business_id') ?: session('business.id'));
        if (! SidebarPermissionUtil::isEnabled('ran', $businessId ?: null)) {
            abort(403, 'The Ran module is disabled for this business.');
        }
        return $next($request);
    }
}
