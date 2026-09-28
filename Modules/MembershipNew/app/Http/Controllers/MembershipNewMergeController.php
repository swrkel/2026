<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewCentralMember;
use Modules\MembershipNew\app\Models\MembershipNewMergeRequest;
use Modules\MembershipNew\app\Services\MembershipNewMergeService;

class MembershipNewMergeController extends Controller
{
    public function index()
    {
        $records = MembershipNewMergeRequest::with(['primaryMember', 'duplicateMember'])->latest()->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(50))->withQueryString();
        $centralMembers = MembershipNewCentralMember::where('is_active', 1)->orderBy('first_name')->limit(1000)->get();

        return view('membershipnew::member_merge.index', compact('records', 'centralMembers'));
    }

    public function store(Request $request, MembershipNewMergeService $service)
    {
        $service->createRequest((int) $request->primary_central_member_id, (int) $request->duplicate_central_member_id, $request->reason);

        return back()->with('status', 'Merge request created.');
    }

    public function approve(int $requestId, MembershipNewMergeService $service)
    {
        $service->approve($requestId);

        return back()->with('status', 'Merge request approved.');
    }

    public function process(int $requestId, MembershipNewMergeService $service)
    {
        $service->process($requestId);

        return back()->with('status', 'Merge request processed.');
    }
}
