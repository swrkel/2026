<?php

namespace Modules\MyHealthMembers\Http\Controllers\Vaccination;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\Vaccination\MyHealthVaccinationService;

class MyHealthVaccinationDashboardController extends Controller
{
    public function index(MyHealthVaccinationService $service)
    {
        return view('myhealthmembers::vaccination.dashboard.index', ['counts' => $service->dashboardCounts()]);
    }
}
