<?php

namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HRManager\Models\HrLeaveType;
use Modules\HRManager\Models\HrLeaveRequest;
use Modules\HRManager\Models\HrLeaveEntitlement;
use Modules\HRManager\Services\HrLeaveService;

class HrLeaveController extends Controller
{
    protected HrLeaveService $leaveService;

    public function __construct(HrLeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    public function dashboard()
    {
        $businessId = session('business.id');

        $requests = HrLeaveRequest::where('business_id', $businessId)->orderByDesc('id')->limit(10)->get();
        $pendingCount = HrLeaveRequest::where('business_id', $businessId)->where('status', 'pending')->count();
        $approvedCount = HrLeaveRequest::where('business_id', $businessId)->where('status', 'approved')->count();
        $rejectedCount = HrLeaveRequest::where('business_id', $businessId)->where('status', 'rejected')->count();
        $typesCount = HrLeaveType::where('business_id', $businessId)->count();

        return view('hrmanager::leave.dashboard', compact('requests', 'pendingCount', 'approvedCount', 'rejectedCount', 'typesCount'));
    }

    public function types()
    {
        $types = HrLeaveType::where('business_id', session('business.id'))->orderBy('name')->paginate(25);
        return view('hrmanager::leave.types', compact('types'));
    }

    public function storeType(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'annual_entitlement' => 'nullable|numeric|min:0',
        ]);

        HrLeaveType::create([
            'business_id' => session('business.id'),
            'code' => $request->code,
            'name' => $request->name,
            'paid_status' => $request->paid_status ?? 'paid',
            'annual_entitlement' => $request->annual_entitlement ?? 0,
            'carry_forward_allowed' => $request->boolean('carry_forward_allowed'),
            'requires_attachment' => $request->boolean('requires_attachment'),
            'status' => $request->status ?? 1,
            'created_by' => auth()->id(),
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Leave type saved successfully.']);
    }

    public function requests()
    {
        $businessId = session('business.id');
        $requests = HrLeaveRequest::where('business_id', $businessId)->orderByDesc('id')->paginate(25);
        $types = HrLeaveType::where('business_id', $businessId)->where('status', 1)->orderBy('name')->get();

        return view('hrmanager::leave.requests', compact('requests', 'types'));
    }

    public function storeRequest(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'leave_type_id' => 'required|integer',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $this->leaveService->createRequest([
            'business_id' => session('business.id'),
            'employee_id' => $request->employee_id,
            'leave_type_id' => $request->leave_type_id,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'reason' => $request->reason,
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Leave request created successfully.']);
    }

    public function approve($id)
    {
        $request = HrLeaveRequest::where('business_id', session('business.id'))->findOrFail($id);
        $this->leaveService->approve($request, auth()->id());
        return back()->with('status', ['success' => 1, 'msg' => 'Leave request approved.']);
    }

    public function reject(Request $request, $id)
    {
        $leaveRequest = HrLeaveRequest::where('business_id', session('business.id'))->findOrFail($id);
        $this->leaveService->reject($leaveRequest, $request->rejected_reason ?? 'Rejected', auth()->id());
        return back()->with('status', ['success' => 1, 'msg' => 'Leave request rejected.']);
    }

    public function balances()
    {
        $balances = HrLeaveEntitlement::where('business_id', session('business.id'))->orderByDesc('leave_year')->paginate(25);
        return view('hrmanager::leave.balances', compact('balances'));
    }
}
