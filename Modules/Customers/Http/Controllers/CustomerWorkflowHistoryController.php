<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Customers\Services\CustomerPermissionService;
use Modules\Customers\Services\CustomerWorkflowService;

class CustomerWorkflowHistoryController extends Controller
{
    public function index(CustomerPermissionService $permissionService, CustomerWorkflowService $workflowService)
    {
        $permissionService->authorize('edit');
        $history = $workflowService->listHistory();
        return view('customers::workflow.history.index', compact('history'));
    }
}
