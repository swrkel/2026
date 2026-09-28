<?php

namespace Modules\PetroDirect\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\PetroDirect\Services\PetroDirectDashboardService;

class DashboardController extends Controller
{
    protected PetroDirectDashboardService $dashboardService;

    public function __construct(PetroDirectDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $summary = $this->dashboardService->summary();

        return view('petrodirect::dashboard.index', compact('summary'));
    }
}
