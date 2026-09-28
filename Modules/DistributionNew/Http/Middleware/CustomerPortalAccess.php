<?php

namespace Modules\DistributionNew\Http\Middleware;

use Closure;
use Modules\DistributionNew\Services\CustomerPortal\CustomerPortalAccessService;

class CustomerPortalAccess
{
    public function handle($request, Closure $next)
    {
        $businessId = (int) session('business.id');
        $userId = (int) optional(auth()->user())->id;
        $portalUser = app(CustomerPortalAccessService::class)->currentPortalUser($businessId, $userId);
        abort_if(!$portalUser, 403, __('distributionnew::customer_portal.access_denied'));
        $request->attributes->set('disnew_customer_portal_user', $portalUser);
        return $next($request);
    }
}
