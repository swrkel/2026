<?php

namespace Modules\ExpensesNew\Reports\Executive;

class YearlyExpenseReport
{
    public string $code = 'yearlyexpense';
    public string $title = 'YearlyExpense Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
