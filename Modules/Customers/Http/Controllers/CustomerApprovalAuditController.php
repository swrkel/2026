<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Customers\Services\CustomerPermissionService;
use Modules\Customers\Services\CustomerWorkflowService;

class CustomerApprovalAuditController extends Controller
{
    public function index(CustomerPermissionService $permissionService, CustomerWorkflowService $workflowService)
    {
        $permissionService->authorize('edit');
        $history = $workflowService->listHistory()->filter(function ($row) {
            return in_array($row->workflow_type, ['customer_approval', 'credit_approval']);
        });
        return view('customers::workflow.audit.index', compact('history'));
    }
}
