<?php

namespace Modules\RestaurantNew\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Services\Admin\FeatureManagerService;
use Modules\RestaurantNew\Services\Admin\UserAccessService;
use Modules\RestaurantNew\Entities\RestaurantNewAuditLog;
use Modules\RestaurantNew\Entities\RestaurantNewDirectUrlBlock;

class SuperAdminController extends Controller
{
    public function features(Request $request, FeatureManagerService $features)
    {
        $businessId = (int) ($request->get('business_id') ?: session('business.id'));
        $locationId = $request->get('location_id') ? (int) $request->get('location_id') : null;
        $items = $businessId ? $features->listForBusiness($businessId, $locationId) : [];
        return view('restaurantnew::admin.features', compact('items', 'businessId', 'locationId'));
    }

    public function saveFeature(Request $request, FeatureManagerService $features)
    {
        $data = $request->validate([
            'business_id' => 'required|integer', 'location_id' => 'nullable|integer',
            'feature_key' => 'required|string', 'is_enabled' => 'required|boolean', 'settings' => 'nullable|array'
        ]);
        $features->saveFeature((int)$data['business_id'], $data['location_id'] ?? null, $data['feature_key'], (bool)$data['is_enabled'], $data['settings'] ?? [], optional(auth()->user())->id);
        return back()->with('status', __('restaurantnew::messages.feature_saved'));
    }

    public function userAccess(Request $request)
    {
        $areas = UserAccessService::ACCESS_AREAS;
        return view('restaurantnew::admin.user_access', compact('areas'));
    }

    public function saveUserAccess(Request $request, UserAccessService $access)
    {
        $data = $request->validate([
            'business_id' => 'required|integer', 'location_id' => 'nullable|integer', 'user_id' => 'required|integer',
            'access_area' => 'required|string', 'allowed_actions' => 'nullable|array', 'is_allowed' => 'required|boolean'
        ]);
        $access->saveRule((int)$data['business_id'], $data['location_id'] ?? null, (int)$data['user_id'], $data['access_area'], $data['allowed_actions'] ?? ['*'], (bool)$data['is_allowed'], optional(auth()->user())->id);
        return back()->with('status', __('restaurantnew::messages.user_access_saved'));
    }

    public function auditLogs(Request $request)
    {
        $logs = RestaurantNewAuditLog::latest()->limit(200)->get();
        return view('restaurantnew::admin.audit_logs', compact('logs'));
    }

    public function blockedUrls(Request $request)
    {
        $blocks = RestaurantNewDirectUrlBlock::latest()->limit(200)->get();
        return view('restaurantnew::admin.blocked_urls', compact('blocks'));
    }
}
