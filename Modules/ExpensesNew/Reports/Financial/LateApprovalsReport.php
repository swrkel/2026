<?php

namespace Modules\ExpensesNew\Reports\Financial;

class LateApprovalsReport
{
    public string $code = 'lateapprovals';
    public string $title = 'LateApprovals Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
