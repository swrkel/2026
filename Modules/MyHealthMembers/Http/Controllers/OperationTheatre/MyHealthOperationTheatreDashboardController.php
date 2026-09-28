<?php

namespace Modules\MyHealthMembers\Http\Controllers\OperationTheatre;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\OperationTheatre\MyHealthOperationTheatreService;

class MyHealthOperationTheatreDashboardController extends Controller
{
    public function index(MyHealthOperationTheatreService $service)
    {
        return view('myhealthmembers::operation_theatre.dashboard.index', [
            'counts' => $service->dashboardCounts(),
        ]);
    }
}
