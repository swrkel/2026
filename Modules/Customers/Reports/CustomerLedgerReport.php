<?php

namespace Modules\Customers\Reports;

use Modules\Customers\Services\CustomerLedgerService;

class CustomerLedgerReport
{
    protected $ledgerService;

    public function __construct(CustomerLedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function data(
        int $businessId,
        ?int $customerId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $rows = $this->ledgerService->ledgerRows(
            $businessId,
            $customerId,
            5000,
            true,
            null,
            $startDate,
            $endDate
        );

        if (empty($customerId)) {
            $rows = $this->ledgerService->withAllCustomerOpeningBalances($rows, $businessId);
        }

        $rows = $this->ledgerService->withBulkPaymentBillDetails($rows, $businessId);

        return [
            'summary' => $this->ledgerService->reportLedgerSummary($rows),
            'rows' => $rows,
            'customers' => $this->ledgerService->reportCustomerOptions($businessId),
        ];
    }
}
