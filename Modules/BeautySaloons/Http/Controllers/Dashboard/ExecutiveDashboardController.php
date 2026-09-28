<?php

namespace Modules\BeautySaloons\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\Dashboard\BeautyAnalyticsService;
use Modules\BeautySaloons\Services\Dashboard\BeautyDashboardMetricService;

class ExecutiveDashboardController extends Controller
{
    public function index(Request $request, BeautyDashboardMetricService $metrics, BeautyAnalyticsService $analytics)
    {
        $filters = $request->only(['start_date', 'end_date', 'business_location_id', 'branch_id', 'staff_id']);
        $data = $analytics->build($filters);
        $charts = $analytics->chartPayload($data);

        return view('beautysaloons::dashboard.executive', compact('data', 'charts', 'filters'));
    }

    public function data(Request $request, BeautyAnalyticsService $analytics)
    {
        $filters = $request->only(['start_date', 'end_date', 'business_location_id', 'branch_id', 'staff_id']);
        $data = $analytics->build($filters);
        return response()->json([
            'success' => true,
            'data' => $data,
            'charts' => $analytics->chartPayload($data),
        ]);
    }
}
