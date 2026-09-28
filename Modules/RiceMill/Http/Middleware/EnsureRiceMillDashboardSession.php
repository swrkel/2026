<?php

namespace Modules\RiceMill\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\RiceMill\Services\DashboardAccessService;
use Modules\RiceMill\Services\DashboardUserAccessService;

class EnsureRiceMillDashboardSession
{
    public function __construct(
        private DashboardAccessService $access,
        private DashboardUserAccessService $users
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $businessId = (int) ($request->session()->get('rice_mill_dashboard_business_id')
            ?: $request->session()->get('user.business_id')
            ?: optional($user)->business_id);

        abort_unless($user && (bool) $request->session()->get('rice_mill_dashboard_mode', false), 403, 'Rice Mill Dashboard login is required.');
        abort_unless($businessId > 0 && (int) $user->business_id === $businessId, 403, 'Rice Mill Dashboard business context is invalid.');
        abort_unless($this->access->moduleEnabled($businessId), 403, 'Rice Mill Module is disabled for this business.');

        $profile = $this->users->accessForUser($businessId, (int) $user->id);
        abort_unless($profile && (int) $profile->enabled === 1, 403, 'Rice Mill Dashboard access is disabled for this user.');
        abort_unless($this->access->canEnter($user, $businessId), 403, 'You do not have permission to access Rice Mill Dashboard.');

        return $next($request);
    }
}
