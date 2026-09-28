<?php

namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\CapacityPlanService;

class CapacityPlanController extends Controller
{
    public function index(Request $request, CapacityPlanService $service)
    {
        $filters = $request->only(['business_id','location_id','store_id','from_date','to_date','vehicle_id','priority']);
        $plans = $service->plans($filters);
        $summary = $service->summary($filters);
        return view('stocktransfernew::capacity.index', compact('plans','summary','filters'));
    }

    public function export(Request $request, CapacityPlanService $service)
    {
        return $service->exportCsv($request->all());
    }
}
