<?php

namespace Modules\LeadsNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\LeadsNew\Services\LeadsNewExecutiveDashboardService;

class LeadsNewExecutiveDashboardController extends Controller
{
    public function index(Request $request, LeadsNewExecutiveDashboardService $service)
    {
        $filters = $request->only(['start_date','end_date','business_id','location_id','assigned_to']);
        return view('leadsnew::dashboard.executive', [
            'summary' => $service->summary($filters),
            'funnel' => $service->funnel($filters),
            'filters' => $filters,
        ]);
    }

    public function data(Request $request, LeadsNewExecutiveDashboardService $service)
    {
        $filters = $request->only(['start_date','end_date','business_id','location_id','assigned_to']);
        return response()->json([
            'summary' => $service->summary($filters),
            'funnel' => $service->funnel($filters),
        ]);
    }
}
