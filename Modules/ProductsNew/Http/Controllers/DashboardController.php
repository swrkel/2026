<?php

namespace Modules\ProductsNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Dashboard\KpiDashboardService;
use Modules\ProductsNew\Services\ProductQueryService;

class DashboardController extends Controller
{
    public function __construct(protected ProductQueryService $products, protected KpiDashboardService $kpis) {}

    public function index()
    {
        $stats = $this->products->dashboardStats();
        $recent = $this->products->paginated([], 10);
        $overview = $this->kpis->overview([]);
        return view('productsnew::dashboard.index', compact('stats', 'recent', 'overview'));
    }
}
