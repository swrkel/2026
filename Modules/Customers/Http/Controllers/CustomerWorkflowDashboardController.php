<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Customers\Services\CustomerPermissionService;
use Modules\Customers\Services\CustomerWorkflowService;
use Modules\Customers\Services\CustomerFeatureAvailability;

class CustomerWorkflowDashboardController extends Controller
{
    public function index(
        CustomerPermissionService $permissionService,
        CustomerWorkflowService $workflowService,
        CustomerFeatureAvailability $featureAvailability
    ) {
        $permissionService->authorize('edit');

        $approvals = $workflowService->approvals();
        $pending = $approvals->where('status', 'pending')->count();
        $approved = $approvals->where('status', 'approved')->count();
        $rejected = $approvals->where('status', 'rejected')->count();
        $history = $workflowService->listHistory()->take(10);
        $missingTables = $featureAvailability->missingTables(CustomerFeatureAvailability::WORKFLOW);

        return view('customers::workflow.dashboard', compact(
            'approvals', 'pending', 'approved', 'rejected', 'history', 'missingTables'
        ));
    }
}
