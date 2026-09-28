<?php

namespace Modules\Customers\Http\Controllers;

use Modules\Customers\Services\CustomerBalanceService;
use Modules\Customers\Services\CustomerPermissionService;
use Modules\Customers\Services\CustomerService;

class CustomerBalanceController extends CustomerActionBaseController
{
    protected $balanceService;

    public function __construct(
        CustomerService $customerService,
        CustomerPermissionService $permissionService,
        CustomerBalanceService $balanceService
    ) {
        parent::__construct($customerService, $permissionService);
        $this->balanceService = $balanceService;
    }

    /**
     * Standalone Customers Module balance details popup.
     *
     * Uses Customers module balance service. It does not route through
     * legacy shared controllers, utilities, or old contact module view files.
     */
    public function show($id)
    {
        $this->permissionService->authorize('view');

        $businessId = (int) request()->session()->get('user.business_id');
        $customer = $this->getCustomer($id);

        $balance_details = $this->balanceService->getCustomerBalance((int) $customer->id, $businessId, $customer);

        return view('customers::balance.index', compact('customer', 'balance_details'));
    }
}
