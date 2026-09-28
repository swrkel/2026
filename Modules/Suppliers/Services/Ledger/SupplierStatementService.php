<?php

namespace Modules\Suppliers\Services\Ledger;

use Modules\Suppliers\Utils\Ledger\SupplierLedgerRowFormatter;

class SupplierStatementService
{
    protected SupplierLedgerQueryService $ledgerQuery;
    protected SupplierLedgerRowFormatter $formatter;

    public function __construct(SupplierLedgerQueryService $ledgerQuery, SupplierLedgerRowFormatter $formatter)
    {
        $this->ledgerQuery = $ledgerQuery;
        $this->formatter = $formatter;
    }

    public function build(int $businessId, int $supplierId, array $filters = []): array
    {
        $supplier = $this->ledgerQuery->supplier($businessId, $supplierId);
        $purchases = $this->ledgerQuery->purchaseQuery($businessId, $supplierId, $filters)->get();
        $payments = $this->ledgerQuery->paymentQuery($businessId, $supplierId, $filters)->get();

        $rows = collect();
        foreach ($purchases as $purchase) { $rows->push($this->formatter->purchase($purchase)); }
        foreach ($payments as $payment) { $rows->push($this->formatter->payment($payment)); }

        $running = 0;
        $rows = $rows->sortBy([['date', 'asc'], ['sort_id', 'asc']])->values()->map(function ($row) use (&$running) {
            $running += ($row['debit'] - $row['credit']);
            $row['balance'] = $running;
            return $row;
        });

        return [
            'supplier' => $supplier,
            'rows' => $rows,
            'summary' => $this->ledgerQuery->balanceSummary($businessId, $supplierId, $filters),
        ];
    }
}
