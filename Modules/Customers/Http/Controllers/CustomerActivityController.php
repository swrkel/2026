<?php

namespace Modules\Customers\Http\Controllers;

use Modules\Customers\Services\CustomerPermissionService;
use Modules\Customers\Services\CustomerService;
use Modules\Customers\Services\CustomerTimelineService;

class CustomerActivityController extends CustomerActionBaseController
{
    protected $timelineService;

    public function __construct(CustomerService $customerService, CustomerPermissionService $permissionService, CustomerTimelineService $timelineService)
    {
        parent::__construct($customerService, $permissionService);
        $this->timelineService = $timelineService;
    }

    public function index($id)
    {
        $this->permissionService->authorize('view');
        $customer = $this->getCustomer($id);
        $businessId = (int) request()->session()->get('user.business_id');
        $timeline = $this->timelineService->timeline($businessId, (int) $customer->id);
        $tableStatus = $this->timelineService->status();

        return view('customers::activity.index', compact('customer', 'timeline', 'tableStatus'));
    }
}
