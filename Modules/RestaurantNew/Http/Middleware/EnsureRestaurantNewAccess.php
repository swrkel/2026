<?php

namespace Modules\RestaurantNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\RestaurantNew\Entities\RestaurantNewDirectUrlBlock;
use Modules\RestaurantNew\Services\Admin\FeatureManagerService;
use Modules\RestaurantNew\Services\Admin\RestaurantNewAuditService;
use Modules\RestaurantNew\Services\Admin\UserAccessService;

class EnsureRestaurantNewAccess
{
    public function handle(Request $request, Closure $next, ?string $area = null, ?string $action = null, ?string $feature = null)
    {
        $businessId = (int) (session('business.id') ?? session('user.business_id') ?? optional(auth()->user())->business_id);
        $locationId = session('business_location_id') ? (int) session('business_location_id') : null;
        $userId = optional(auth()->user())->id;

        if (!$businessId || !$userId) {
            return $this->block($request, $businessId ?: null, $locationId, $userId, $area, 'Missing business/user context');
        }

        if ($feature && !app(FeatureManagerService::class)->isEnabled($businessId, $locationId, $feature)) {
            return $this->block($request, $businessId, $locationId, $userId, $area, 'Feature disabled');
        }

        if ($area && !app(UserAccessService::class)->isAllowed($businessId, $locationId, $userId, $area, $action)) {
            return $this->block($request, $businessId, $locationId, $userId, $area, 'Permission denied');
        }

        return $next($request);
    }

    protected function block(Request $request, ?int $businessId, ?int $locationId, ?int $userId, ?string $area, string $reason)
    {
        RestaurantNewDirectUrlBlock::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'user_id' => $userId,
            'route_name' => optional($request->route())->getName(),
            'url_path' => $request->path(),
            'required_permission' => $area,
            'block_reason' => $reason,
        ]);
        if ($businessId) {
            app(RestaurantNewAuditService::class)->record($businessId, $locationId, $userId, 'security', 'direct_url_blocked', 'route', null, [], ['path' => $request->path(), 'reason' => $reason], $request);
        }
        abort(403, 'RestaurantNew access denied: '.$reason);
    }
}
