<?php

namespace Modules\ProductsNew\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Dashboard\KpiDashboardService;

class KpiDashboardController extends Controller
{
    public function __construct(protected KpiDashboardService $service) {}

    public function index(Request $request)
    {
        $filters = $request->only(['location_id', 'date_range']);
        $overview = $this->service->overview($filters);
        return view('productsnew::dashboard.kpi', compact('overview', 'filters'));
    }

    public function snapshot(Request $request)
    {
        $id = $this->service->storeSnapshot($request->only(['location_id', 'date_range']));
        return back()->with('status', __('productsnew::product.dashboard_snapshot_saved') . ' #' . $id);
    }
}
