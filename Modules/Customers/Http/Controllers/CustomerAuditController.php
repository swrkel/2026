<?php

namespace Modules\Customers\Http\Controllers;

use Modules\Customers\Services\CustomerAuditService;
use Modules\Customers\Services\CustomerPermissionService;
use Modules\Customers\Services\CustomerService;
use Modules\Customers\Services\CustomerTimelineService;

class CustomerAuditController extends CustomerActionBaseController
{
    protected $auditService;
    protected $timelineService;

    public function __construct(CustomerService $customerService, CustomerPermissionService $permissionService, CustomerAuditService $auditService, CustomerTimelineService $timelineService)
    {
        parent::__construct($customerService, $permissionService);
        $this->auditService = $auditService;
        $this->timelineService = $timelineService;
    }

    public function index($id)
    {
        $this->permissionService->authorize('view');
        $customer = $this->getCustomer($id);
        $businessId = (int) request()->session()->get('user.business_id');
        $activities = $this->auditService->activities($businessId, (int) $customer->id);
        $notes = $this->auditService->notes($businessId, (int) $customer->id);
        $attachments = $this->auditService->attachments($businessId, (int) $customer->id);
        $timeline = $this->timelineService->timelineFromCollections($notes, $activities, $attachments);
        $tableStatus = $this->auditService->tableStatus();

        return view('customers::audit.index', compact('customer', 'activities', 'timeline', 'tableStatus'));
    }
}
