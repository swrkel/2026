<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MembershipNew\app\Health\MembershipNewHealthCheck;

class MembershipNewHealthController extends Controller
{
    public function index(MembershipNewHealthCheck $healthCheck)
    {
        $results = $healthCheck->run();

        return view('membershipnew::health.index', compact('results'));
    }
}
