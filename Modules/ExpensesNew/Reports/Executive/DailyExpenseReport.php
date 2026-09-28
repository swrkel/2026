<?php

namespace Modules\ExpensesNew\Reports\Executive;

class DailyExpenseReport
{
    public string $code = 'dailyexpense';
    public string $title = 'DailyExpense Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
