<?php

namespace Modules\Customers\Services;

use Modules\Customers\Entities\Customer;

class CustomerBalanceService
{
    protected CustomerLedgerService $ledgerService;

    public function __construct(CustomerLedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Lightweight balance details for the Customer Register action popup.
     * Totals are calculated in SQL by CustomerLedgerService; no ledger row list
     * is loaded because this popup displays summary values only.
     */
    public function getCustomerBalance(int $customerId, int $businessId, ?Customer $customer = null): array
    {
        $summary = $this->ledgerService->ledgerSummary($businessId, $customerId, $customer);
        $openingBalance = (float) ($customer->opening_balance ?? 0);
        $totalDebit = (float) ($summary['debit'] ?? 0);
        $totalCredit = (float) ($summary['credit'] ?? 0);

        return [
            'total_sale' => max($totalDebit - max($openingBalance, 0), 0),
            'opening_balance' => $openingBalance,
            'total_paid' => $totalCredit,
            'total_balance' => (float) ($summary['balance'] ?? ($totalDebit - $totalCredit)),
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'balance' => (float) ($summary['balance'] ?? ($totalDebit - $totalCredit)),
            'source' => 'customers_module_aggregate',
        ];
    }
}
