<?php

namespace Modules\MyHealthMembers\Http\Controllers\DisasterRecovery;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\DisasterRecovery\MyHealthDisasterRecoveryService;

class MyHealthDisasterRecoveryDashboardController extends Controller
{
    public function index(MyHealthDisasterRecoveryService $service)
    {
        $dashboard = $service->dashboard();
        return view('myhealthmembers::disaster_recovery.dashboard', compact('dashboard'));
    }
}
