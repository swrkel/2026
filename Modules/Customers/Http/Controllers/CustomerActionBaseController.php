<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Customers\Services\CustomerService;
use Modules\Customers\Services\CustomerPermissionService;

abstract class CustomerActionBaseController extends Controller
{
    protected $customerService;
    protected $permissionService;

    public function __construct(CustomerService $customerService, CustomerPermissionService $permissionService)
    {
        $this->customerService = $customerService;
        $this->permissionService = $permissionService;
    }

    protected function getCustomer($id)
    {
        $businessId = (int) request()->session()->get('user.business_id');
        return $this->customerService->findCustomer($businessId, (int) $id);
    }
}
