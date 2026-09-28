<?php

namespace Modules\MyHealthMembers\Http\Controllers\DisasterRecovery;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\DisasterRecovery\MyHealthDisasterRecoveryService;

class MyHealthDisasterRecoveryReportController extends Controller
{
    public function index(Request $request, MyHealthDisasterRecoveryService $service)
    {
        $reports = $service->reports($request->all());
        return view('myhealthmembers::disaster_recovery.reports.index', compact('reports'));
    }
}
