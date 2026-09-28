<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customers\Services\CustomerPermissionService;
use Modules\Customers\Services\CustomerWorkflowService;

class CustomerCreditApprovalController extends Controller
{
    public function index(CustomerPermissionService $permissionService, CustomerWorkflowService $workflowService)
    {
        $permissionService->authorize('edit');
        $approvals = $workflowService->approvals('credit_approval');
        $title = 'Customer Credit Approvals';
        return view('customers::workflow.approvals.index', compact('approvals', 'title'));
    }

    public function approve(Request $request, $id, CustomerPermissionService $permissionService, CustomerWorkflowService $workflowService)
    {
        $permissionService->authorize('edit');
        $workflowService->approve((int) $id, $request->input('remarks'));
        return back()->with('status', ['success' => 1, 'msg' => 'Credit approval completed successfully.']);
    }

    public function reject(Request $request, $id, CustomerPermissionService $permissionService, CustomerWorkflowService $workflowService)
    {
        $permissionService->authorize('edit');
        $workflowService->reject((int) $id, $request->input('remarks'));
        return back()->with('status', ['success' => 1, 'msg' => 'Credit approval rejected successfully.']);
    }
}
