<?php

namespace Modules\Customers\Exports;

class CustomerStatementExport extends CustomerCsvExport
{
    public function downloadStatement(string $filename, iterable $statementRows)
    {
        $rows = [];
        foreach ($statementRows as $row) {
            $type = ucwords(str_replace('_', ' ', (string) $this->value($row, 'type')));
            $status = ucwords(str_replace('_', ' ', (string) $this->value($row, 'payment_status')));

            $rows[] = [
                $this->dateValue($this->value($row, 'transaction_date')),
                $this->value($row, 'customer_name'),
                $this->value($row, 'invoice_no', $this->value($row, 'ref_no')),
                trim($type . ' / ' . $status, ' /'),
                $this->moneyValue($this->value($row, 'final_total', 0)),
            ];
        }

        return $this->download($filename, [
            'Date', 'Customer', 'Reference', 'Description', 'Amount'
        ], $rows);
    }
}
