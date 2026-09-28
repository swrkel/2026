<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customers\Services\CustomerPermissionService;
use Modules\Customers\Services\CustomerWorkflowService;

class CustomerStatusController extends Controller
{
    public function index(CustomerPermissionService $permissionService, CustomerWorkflowService $workflowService)
    {
        $permissionService->authorize('edit');
        $customers = $workflowService->customers();
        $history = $workflowService->listHistory()->where('workflow_type', 'status_change');
        return view('customers::workflow.status.index', compact('customers', 'history'));
    }

    public function change(Request $request, CustomerPermissionService $permissionService, CustomerWorkflowService $workflowService)
    {
        $permissionService->authorize('edit');
        $request->validate([
            'contact_id' => 'required|integer',
            'status' => 'required|string|max:50',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $workflowService->changeStatus((int) $request->contact_id, $request->status, $request->remarks);

        return back()->with('status', ['success' => 1, 'msg' => 'Customer status updated successfully.']);
    }
}
