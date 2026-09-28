<?php

namespace Modules\BeautySaloons\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\Dashboard\BeautyDashboardMetricService;

class StaffDashboardController extends Controller
{
    public function index(Request $request, BeautyDashboardMetricService $metrics)
    {
        $filters = $request->only(['staff_id', 'start_date', 'end_date']);
        $data = $metrics->staff($filters);
        return view('beautysaloons::dashboard.staff', compact('data', 'filters'));
    }
}
