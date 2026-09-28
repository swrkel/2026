<?php

namespace Modules\Customers\Exports;

class CustomerLedgerExport extends CustomerCsvExport
{
    public function downloadLedger(string $filename, iterable $ledgerRows)
    {
        $rows = [];
        foreach ($ledgerRows as $row) {
            $rows[] = [
                $this->dateValue($this->value($row, 'transaction_date')),
                $this->value($row, 'customer_name'),
                $this->value($row, 'customer_code'),
                $this->value($row, 'invoice_no', $this->value($row, 'ref_no')),
                ucwords(str_replace('_', ' ', (string) $this->value($row, 'type'))),
                ucwords(str_replace('_', ' ', (string) $this->value($row, 'payment_status'))),
                $this->moneyValue($this->value($row, 'amount', $this->value($row, 'final_total', 0))),
            ];
        }

        return $this->download($filename, [
            'Date', 'Customer', 'Customer Code', 'Invoice/Ref', 'Type', 'Status', 'Amount'
        ], $rows);
    }
}
