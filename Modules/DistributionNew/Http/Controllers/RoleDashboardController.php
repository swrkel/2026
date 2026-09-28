<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DistributionNew\Services\Dashboards\RoleDashboardService;

class RoleDashboardController extends Controller
{
    public function index(Request $request, RoleDashboardService $service)
    {
        $businessId = (int) session('business.id', $request->get('business_id'));
        $locationId = $request->get('business_location_id') ? (int) $request->get('business_location_id') : null;
        $role = $request->get('role', 'manager');

        $cards = $service->cards($businessId, $locationId, $role);

        return view('distributionnew::dashboard.role-dashboard', compact('cards', 'role'));
    }
}
