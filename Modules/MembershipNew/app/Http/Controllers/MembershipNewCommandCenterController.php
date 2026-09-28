<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MembershipNew\app\Services\MembershipNewCommandCenterService;

class MembershipNewCommandCenterController extends Controller
{
    public function index(MembershipNewCommandCenterService $service)
    {
        $summary = $service->summary(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId());

        return view('membershipnew::command_center.index', compact('summary'));
    }
}
