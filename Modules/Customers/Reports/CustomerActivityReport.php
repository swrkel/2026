<?php

namespace Modules\Customers\Reports;

use Modules\Customers\Services\CustomerLedgerService;

class CustomerActivityReport
{
    protected $ledgerService;

    public function __construct(CustomerLedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function customerListData(int $businessId): array
    {
        return [
            'summary' => $this->ledgerService->summary($businessId),
            'customers' => $this->ledgerService->customerList($businessId),
        ];
    }

    public function inactiveCustomersData(int $businessId): array
    {
        return [
            'summary' => $this->ledgerService->summary($businessId),
            'customers' => $this->ledgerService->inactiveCustomers($businessId),
        ];
    }
}
