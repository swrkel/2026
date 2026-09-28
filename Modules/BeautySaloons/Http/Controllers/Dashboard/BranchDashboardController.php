<?php

namespace Modules\BeautySaloons\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\Dashboard\BeautyDashboardMetricService;

class BranchDashboardController extends Controller
{
    public function index(Request $request, BeautyDashboardMetricService $metrics)
    {
        $filters = $request->only(['start_date', 'end_date', 'business_location_id', 'branch_id']);
        $data = $metrics->branchManager($filters);
        return view('beautysaloons::dashboard.branch', compact('data', 'filters'));
    }

    public function data(Request $request, BeautyDashboardMetricService $metrics)
    {
        return response()->json(['success' => true, 'data' => $metrics->branchManager($request->all())]);
    }
}
