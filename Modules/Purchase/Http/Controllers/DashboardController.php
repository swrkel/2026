<?php

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Dashboard\PurchaseDashboardService;

class DashboardController extends Controller
{
    public function index(PurchaseDashboardService $service)
    {
        return view('purchase::dashboard.index', [
            'summary' => $service->summary(),
        ]);
    }
}
