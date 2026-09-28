<?php

namespace Modules\ExpensesNew\Reports\Executive;

class ExpenseBySourceReport
{
    public string $code = 'expensebysource';
    public string $title = 'ExpenseBySource Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
