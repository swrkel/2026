<?php

namespace Modules\RestaurantNew\Http\Controllers\Hardening;

use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewIntegrityCheck;
use Modules\RestaurantNew\Entities\RestaurantNewTenantScopeLog;
use Modules\RestaurantNew\Services\Hardening\RestaurantNewDependencyGuardService;
use Modules\RestaurantNew\Services\Hardening\RestaurantNewIntegrityService;
use Modules\RestaurantNew\Services\Hardening\RestaurantNewScopeService;

class IntegrityController extends Controller
{
    public function index()
    {
        $checks = RestaurantNewIntegrityCheck::latest('checked_at')->limit(100)->get();
        return view('restaurantnew::hardening.integrity', compact('checks'));
    }

    public function run()
    {
        $scope = app(RestaurantNewScopeService::class);
        app(RestaurantNewIntegrityService::class)->run($scope->businessId(), $scope->locationId());
        return redirect()->back()->with('status', __('restaurantnew::messages.integrity_checks_completed'));
    }

    public function dependencyScan()
    {
        $scan = app(RestaurantNewDependencyGuardService::class)->scanModule();
        return view('restaurantnew::hardening.dependencies', compact('scan'));
    }

    public function scopeLogs()
    {
        $logs = RestaurantNewTenantScopeLog::latest()->limit(200)->get();
        return view('restaurantnew::hardening.scope_logs', compact('logs'));
    }
}
