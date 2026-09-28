<?php

namespace Modules\ExpensesNew\Reports\Operational;

class RecurringExpenseReport
{
    public string $code = 'recurringexpense';
    public string $title = 'RecurringExpense Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
