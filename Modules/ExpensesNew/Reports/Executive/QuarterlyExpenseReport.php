<?php

namespace Modules\ExpensesNew\Reports\Executive;

class QuarterlyExpenseReport
{
    public string $code = 'quarterlyexpense';
    public string $title = 'QuarterlyExpense Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
