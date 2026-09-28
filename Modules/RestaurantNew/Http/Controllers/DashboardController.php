<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Services\DashboardService;

class DashboardController extends Controller
{
    public function index(DashboardService $service)
    {
        $summary = $service->summary();
        return view('restaurantnew::dashboard.index', compact('summary'));
    }
}
