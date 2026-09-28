<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\DistributionNew\Models\DisnewDeploymentLog;

class DeploymentLogController extends Controller
{
    public function index()
    {
        $logs = DisnewDeploymentLog::latest()->limit(100)->get();
        return view('distributionnew::reports.deployment-logs', compact('logs'));
    }
}
