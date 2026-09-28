<?php

namespace Modules\ExpensesNew\Reports\Management;

class ExpenseTrendsReport
{
    public string $code = 'expensetrends';
    public string $title = 'ExpenseTrends Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
