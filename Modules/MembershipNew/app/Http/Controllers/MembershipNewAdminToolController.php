<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MembershipNew\app\Services\MembershipNewAdminToolService;

class MembershipNewAdminToolController extends Controller
{
    public function index(MembershipNewAdminToolService $service)
    {
        $counts = $service->counts();

        return view('membershipnew::admin_tools.index', compact('counts'));
    }

    public function resetDemoData(MembershipNewAdminToolService $service)
    {
        $deleted = $service->resetDemoData(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId());

        return back()->with('status', 'Demo data reset completed: ' . json_encode($deleted));
    }
}
