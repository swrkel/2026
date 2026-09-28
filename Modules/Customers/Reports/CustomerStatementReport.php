<?php

namespace Modules\Customers\Reports;

use Modules\Customers\Services\CustomerLedgerService;

class CustomerStatementReport
{
    protected $ledgerService;

    public function __construct(CustomerLedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function data(int $businessId, ?int $customerId = null): array
    {
        return [
            // MA-002 (S-614): the summary follows the selected customer, so the
            // totals at the top match the rows underneath.
            'summary' => $this->ledgerService->summary($businessId, $customerId),
            'rows' => $this->ledgerService->statementRows($businessId, $customerId),
        ];
    }
}
