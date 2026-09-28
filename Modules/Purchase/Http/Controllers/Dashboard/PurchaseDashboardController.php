<?php

namespace Modules\Purchase\Http\Controllers\Dashboard;

use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Dashboard\PurchaseDashboardService;

class PurchaseDashboardController extends Controller
{
    public function index(PurchaseDashboardService $service)
    {
        return view('purchase::dashboard.index', [
            'summary' => $service->summary(),
            'filters' => $service->filters(),
        ]);
    }
}
