<?php

namespace Modules\MyHealthMembers\Http\Controllers\DisasterRecovery;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthSystemHealthCheck;
use Modules\MyHealthMembers\Services\DisasterRecovery\MyHealthDisasterRecoveryService;

class MyHealthHealthMonitorController extends Controller
{
    public function index()
    {
        $checks = MyHealthSystemHealthCheck::orderByDesc('checked_at')->paginate(50);
        return view('myhealthmembers::disaster_recovery.health.index', compact('checks'));
    }

    public function run(MyHealthDisasterRecoveryService $service)
    {
        $service->runHealthChecks();
        return redirect()->route('myhealth.disaster_recovery.health.index')->with('status', 'Health checks completed.');
    }
}
