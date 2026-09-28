<?php

namespace Modules\FinanceReports\Services\Enterprise;

class ReportBuilderService
{
    public function fields(): array
    {
        return [
            'common' => ['Date', 'Branch / Location', 'Account', 'Reference', 'Voucher No', 'Debit', 'Credit', 'Amount', 'Running Balance'],
            'party' => ['Customer', 'Supplier', 'Mobile', 'NIC / BR No', 'Outstanding', 'Ageing Bucket'],
            'inventory' => ['Product', 'Category', 'Quantity', 'Cost', 'Value', 'Turnover'],
            'module' => ['Source Module', 'Source Document', 'Source User', 'Source Status'],
        ];
    }

    public function templates(): array
    {
        return [
            ['name' => 'Monthly Finance Summary', 'group_by' => 'Branch', 'output' => 'PDF / Excel'],
            ['name' => 'Branch Profitability', 'group_by' => 'Branch + Account Group', 'output' => 'Dashboard / Excel'],
            ['name' => 'Customer Collection Behaviour', 'group_by' => 'Customer + Ageing Bucket', 'output' => 'Excel'],
            ['name' => 'Cross Module Revenue', 'group_by' => 'Module + Branch', 'output' => 'Dashboard'],
        ];
    }
}
