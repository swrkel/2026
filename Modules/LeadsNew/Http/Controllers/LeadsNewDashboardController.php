<?php

namespace Modules\LeadsNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\LeadsNew\Services\LeadsNewDashboardService;

class LeadsNewDashboardController extends Controller
{
    public function index(LeadsNewDashboardService $service)
    {
        return view('leadsnew::dashboard.index', $service->summary());
    }
}
