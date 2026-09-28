<?php

namespace Modules\ExpensesNew\Reports\Financial;

class ApprovalHistoryReport
{
    public string $code = 'approvalhistory';
    public string $title = 'ApprovalHistory Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
