<?php

namespace Modules\ExpensesNew\Reports\Operational;

class ExpenseDetailReport
{
    public string $code = 'expensedetail';
    public string $title = 'ExpenseDetail Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
