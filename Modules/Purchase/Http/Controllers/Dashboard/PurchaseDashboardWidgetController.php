<?php

namespace Modules\Purchase\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Dashboard\PurchaseDashboardService;

class PurchaseDashboardWidgetController extends Controller
{
    public function summary(Request $request, PurchaseDashboardService $service)
    {
        return response()->json($service->summary($request->all()));
    }

    public function outstanding(Request $request, PurchaseDashboardService $service)
    {
        return response()->json($service->outstanding($request->all()));
    }

    public function recentPurchases(Request $request, PurchaseDashboardService $service)
    {
        return response()->json($service->recentPurchases($request->all()));
    }
}
