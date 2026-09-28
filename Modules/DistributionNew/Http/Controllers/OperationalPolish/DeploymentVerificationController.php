<?php

namespace Modules\DistributionNew\Http\Controllers\OperationalPolish;

use Illuminate\Routing\Controller;
use Modules\DistributionNew\Services\OperationalPolish\DeploymentVerificationService;

class DeploymentVerificationController extends Controller
{
    public function index()
    {
        return view('distributionnew::operational_polish.deployment_verification.index');
    }
}
