<?php

namespace Modules\MyHealthMembers\Http\Controllers\Nursing;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\Nursing\MyHealthNursingService;

class MyHealthNursingDashboardController extends Controller
{
    public function index(MyHealthNursingService $service)
    {
        return view('myhealthmembers::nursing.dashboard.index', [
            'counts' => $service->dashboardCounts(),
        ]);
    }
}
