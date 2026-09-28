<?php

namespace Modules\ExpensesNew\Reports\Executive;

class WeeklyExpenseReport
{
    public string $code = 'weeklyexpense';
    public string $title = 'WeeklyExpense Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
