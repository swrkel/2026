<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Reports\StaffPerformanceReportService;

class StaffReportController extends Controller
{
    public function performance(Request $request, StaffPerformanceReportService $service)
    {
        $rows = $service->waiterSales($request->only(['business_id', 'location_id']));
        return view('restaurantnew::reports.staff-performance', compact('rows'));
    }

    public function shifts(Request $request, StaffPerformanceReportService $service)
    {
        $rows = $service->cashierShiftSummary($request->only(['business_id', 'location_id']));
        return view('restaurantnew::reports.shift-summary', compact('rows'));
    }
}
