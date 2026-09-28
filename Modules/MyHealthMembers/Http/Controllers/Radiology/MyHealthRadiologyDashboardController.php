<?php

namespace Modules\MyHealthMembers\Http\Controllers\Radiology;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\Radiology\MyHealthRadiologyService;

class MyHealthRadiologyDashboardController extends Controller
{
    public function index(MyHealthRadiologyService $service)
    {
        return view('myhealthmembers::radiology.dashboard.index', [
            'counts' => $service->dashboardCounts(),
        ]);
    }
}
