<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customers\Services\CustomerPermissionService;
use Modules\Customers\Services\CustomerWorkflowService;

class CustomerActivationController extends Controller
{
    public function activate(Request $request, $customer, CustomerPermissionService $permissionService, CustomerWorkflowService $workflowService)
    {
        $permissionService->authorize('edit');
        $workflowService->changeStatus((int) $customer, 'active', $request->input('remarks'));
        return back()->with('status', ['success' => 1, 'msg' => 'Customer activated successfully.']);
    }

    public function deactivate(Request $request, $customer, CustomerPermissionService $permissionService, CustomerWorkflowService $workflowService)
    {
        $permissionService->authorize('edit');
        $workflowService->changeStatus((int) $customer, 'inactive', $request->input('remarks'));
        return back()->with('status', ['success' => 1, 'msg' => 'Customer deactivated successfully.']);
    }
}
