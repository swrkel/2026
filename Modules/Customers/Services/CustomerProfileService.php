<?php

namespace Modules\Customers\Services;

use Modules\Customers\Entities\CustomerGroup;
use Modules\Customers\Entities\CustomerUser;

class CustomerProfileService
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function profileData(int $businessId, int $customerId): array
    {
        $customer = $this->customerService->findCustomer($businessId, $customerId);

        $customerGroup = null;
        if (!empty($customer->customer_group_id)) {
            $customerGroup = CustomerGroup::where('business_id', $businessId)
                ->where('id', $customer->customer_group_id)
                ->first();
        }

        $assignedOfficer = null;
        if (!empty($customer->created_by)) {
            $assignedOfficer = CustomerUser::where('business_id', $businessId)
                ->where('id', $customer->created_by)
                ->first();
        }

        return compact('customer', 'customerGroup', 'assignedOfficer');
    }
}
