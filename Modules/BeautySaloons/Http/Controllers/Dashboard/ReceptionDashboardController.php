<?php

namespace Modules\BeautySaloons\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\Dashboard\BeautyDashboardMetricService;

class ReceptionDashboardController extends Controller
{
    public function index(Request $request, BeautyDashboardMetricService $metrics)
    {
        $filters = $request->only(['business_location_id', 'branch_id']);
        $data = $metrics->reception($filters);
        return view('beautysaloons::dashboard.reception', compact('data', 'filters'));
    }
}
