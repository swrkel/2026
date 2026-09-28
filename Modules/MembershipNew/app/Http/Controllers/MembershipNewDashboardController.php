<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MembershipNew\app\Models\MembershipNewLinkedBusiness;
use Modules\MembershipNew\app\Models\MembershipNewMember;
use Modules\MembershipNew\app\Models\MembershipNewPointRule;
use Modules\MembershipNew\app\Models\MembershipNewPointTransaction;
use Modules\MembershipNew\app\Models\MembershipNewShareHolding;

class MembershipNewDashboardController extends Controller
{
    public function index()
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();

        $summary = [
            'members' => MembershipNewMember::forBusiness($businessId)->count(),
            'linked_businesses' => MembershipNewLinkedBusiness::forBusiness($businessId)->where('is_active', 1)->count(),
            'point_rules' => MembershipNewPointRule::forBusiness($businessId)->where('is_active', 1)->count(),
            'point_balance' => MembershipNewPointTransaction::forBusiness($businessId)->sum('points'),
            'shares' => MembershipNewShareHolding::forBusiness($businessId)->sum('shares'),
        ];

        return view('membershipnew::dashboard.index', compact('summary'));
    }
}
