<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewCentralMember;
use Modules\MembershipNew\app\Models\MembershipNewMemberBusinessMap;
use Modules\MembershipNew\app\Services\MembershipNewCentralMemberService;

class MembershipNewBusinessMemberController extends Controller
{
    public function index()
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $records = MembershipNewMemberBusinessMap::with('centralMember')
            ->forBusiness($businessId)
            ->latest()
            ->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(50))->withQueryString();

        $centralMembers = MembershipNewCentralMember::where('is_active', 1)->orderBy('first_name')->limit(500)->get();

        return view('membershipnew::business_members.index', compact('records', 'centralMembers'));
    }

    public function link(Request $request, MembershipNewCentralMemberService $service)
    {
        $service->linkToBusiness(
            (int) $request->central_member_id,
            \Modules\MembershipNew\app\Support\MembershipNewContext::businessId(),
            $request->local_customer_id ? (int) $request->local_customer_id : null,
            $request->local_member_id ? (int) $request->local_member_id : null
        );

        return back()->with('status', 'Central member linked to this business.');
    }
}
