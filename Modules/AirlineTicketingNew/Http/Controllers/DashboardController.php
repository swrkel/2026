<?php

namespace Modules\AirlineTicketingNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Services\Dashboard\DashboardService;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function index()
    {
        $businessId = (int) session('business.id');
        $summary = $this->dashboardService->summary($businessId);

        return view('airlineticketingnew::dashboard.index', compact('summary'));
    }
}
