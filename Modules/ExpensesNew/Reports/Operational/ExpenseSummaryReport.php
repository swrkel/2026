<?php

namespace Modules\ExpensesNew\Reports\Operational;

class ExpenseSummaryReport
{
    public string $code = 'expensesummary';
    public string $title = 'ExpenseSummary Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
