<?php

namespace Modules\MyHealthMembers\Http\Controllers\Laboratory;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\Laboratory\MyHealthLaboratoryService;

class MyHealthLaboratoryDashboardController extends Controller
{
    public function index(MyHealthLaboratoryService $service)
    {
        return view('myhealthmembers::laboratory.dashboard.index', [
            'counts' => $service->dashboardCounts(),
        ]);
    }
}
