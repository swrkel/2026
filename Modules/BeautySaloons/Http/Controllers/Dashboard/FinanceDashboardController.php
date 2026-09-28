<?php

namespace Modules\BeautySaloons\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\Dashboard\BeautyDashboardMetricService;

class FinanceDashboardController extends Controller
{
    public function index(Request $request, BeautyDashboardMetricService $metrics)
    {
        $filters = $request->only(['start_date', 'end_date', 'business_location_id', 'branch_id']);
        $data = $metrics->finance($filters);
        return view('beautysaloons::dashboard.finance', compact('data', 'filters'));
    }
}
