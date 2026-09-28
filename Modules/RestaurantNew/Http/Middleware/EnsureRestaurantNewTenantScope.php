<?php

namespace Modules\RestaurantNew\Http\Middleware;

use Closure;
use Modules\RestaurantNew\Services\Hardening\RestaurantNewScopeService;

class EnsureRestaurantNewTenantScope
{
    public function handle($request, Closure $next)
    {
        $scope = app(RestaurantNewScopeService::class);
        if (!$scope->businessId()) {
            $scope->logScopeDecision('route_access', 'blocked', 'Missing business scope.');
            abort(403, 'RestaurantNew business scope is missing.');
        }
        if (config('restaurantnew.hardening.require_location_scope_for_operational_pages') && $request->is('restaurant-new/pos*') && !$scope->locationId()) {
            $scope->logScopeDecision('route_access', 'blocked', 'Missing location scope for operational page.');
            abort(403, 'RestaurantNew location scope is missing.');
        }
        $scope->logScopeDecision('route_access', 'allowed', 'Tenant scope verified.');
        return $next($request);
    }
}
