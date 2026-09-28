<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customers\Services\CustomerPermissionService;
use Modules\Customers\Services\CustomerWorkflowService;

class CustomerApprovalController extends Controller
{
    protected $workflowService;
    protected $permissionService;

    public function __construct(CustomerWorkflowService $workflowService, CustomerPermissionService $permissionService)
    {
        $this->workflowService = $workflowService;
        $this->permissionService = $permissionService;
    }

    public function index()
    {
        $this->permissionService->authorize('edit');
        $approvals = $this->workflowService->approvals('customer_approval');
        $title = 'Customer Approvals';
        return view('customers::workflow.approvals.index', compact('approvals', 'title'));
    }

    public function create()
    {
        $this->permissionService->authorize('edit');
        $customers = $this->workflowService->customers();
        $title = 'Request Customer Approval';
        $workflowType = 'customer_approval';
        return view('customers::workflow.approvals.form', compact('customers', 'title', 'workflowType'));
    }

    public function store(Request $request)
    {
        $this->permissionService->authorize('edit');
        $request->validate([
            'contact_id' => 'required|integer',
            'workflow_type' => 'required|string|max:50',
            'reason' => 'nullable|string|max:1000',
        ]);

        $this->workflowService->createApproval($request->only(['contact_id', 'workflow_type', 'current_value', 'requested_value', 'reason']));

        return redirect()->route('customers.workflow.approvals.index')->with('status', ['success' => 1, 'msg' => 'Customer approval request saved successfully.']);
    }

    public function show($id)
    {
        $this->permissionService->authorize('edit');
        $approval = $this->workflowService->approval((int) $id);
        abort_if(empty($approval), 404);
        return view('customers::workflow.approvals.show', compact('approval'));
    }

    public function approve(Request $request, $id)
    {
        $this->permissionService->authorize('edit');
        $this->workflowService->approve((int) $id, $request->input('remarks'));
        return back()->with('status', ['success' => 1, 'msg' => 'Approval completed successfully.']);
    }

    public function reject(Request $request, $id)
    {
        $this->permissionService->authorize('edit');
        $this->workflowService->reject((int) $id, $request->input('remarks'));
        return back()->with('status', ['success' => 1, 'msg' => 'Approval rejected successfully.']);
    }
}
