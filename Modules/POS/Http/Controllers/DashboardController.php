<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Services\POSDashboardService;

class DashboardController extends Controller
{
    protected POSDashboardService $dashboardService;

    public function __construct(POSDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->get('location_id');
        $dateRange = $request->get('date_range');

        $summary = $this->dashboardService->summary($businessId, $locationId, $dateRange);

        return view('pos::dashboard.index', compact('summary', 'dateRange', 'locationId'));
    }

    public function status(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $status = $this->dashboardService->moduleStatus($businessId);

        return view('pos::dashboard.status', compact('status'));
    }
}
