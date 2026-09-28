<?php

namespace Modules\MyHealthMembers\Http\Controllers\DisasterRecovery;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\DisasterRecovery\MyHealthDisasterRecoveryService;

class MyHealthRecoveryTestController extends Controller
{
    public function index(MyHealthDisasterRecoveryService $service)
    {
        $tests = $service->recoveryTests();
        return view('myhealthmembers::disaster_recovery.tests.index', compact('tests'));
    }
}
