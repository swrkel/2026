<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewApprovalRequest;
use Modules\MembershipNew\app\Services\MembershipNewApprovalService;

class MembershipNewApprovalController extends Controller
{
    public function index()
    {
        $records = MembershipNewApprovalRequest::forBusiness(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId())->latest()->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(50))->withQueryString();

        return view('membershipnew::approvals.index', compact('records'));
    }

    public function store(Request $request, MembershipNewApprovalService $service)
    {
        $data = $request->all();
        $data['business_id'] = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $service->requestApproval($data);

        return back()->with('status', 'Approval request created.');
    }

    public function approve(Request $request, int $requestId, MembershipNewApprovalService $service)
    {
        $service->approve(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), $requestId, $request->approval_note);

        return back()->with('status', 'Approval request approved.');
    }

    public function reject(Request $request, int $requestId, MembershipNewApprovalService $service)
    {
        $service->reject(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), $requestId, $request->rejection_note);

        return back()->with('status', 'Approval request rejected.');
    }
}
