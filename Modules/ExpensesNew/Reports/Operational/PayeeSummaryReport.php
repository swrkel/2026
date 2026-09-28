<?php

namespace Modules\ExpensesNew\Reports\Operational;

class PayeeSummaryReport
{
    public string $code = 'payeesummary';
    public string $title = 'PayeeSummary Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
