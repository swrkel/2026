<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Reports\DeliveryReportService;

class DeliveryReportController extends Controller
{
    public function summary(Request $request, DeliveryReportService $reports)
    {
        $rows = $reports->summary($request->all());
        return view('restaurantnew::reports.delivery-summary', compact('rows'));
    }

    public function riders(Request $request, DeliveryReportService $reports)
    {
        $rows = $reports->riderPerformance($request->all());
        return view('restaurantnew::reports.delivery-riders', compact('rows'));
    }
}
