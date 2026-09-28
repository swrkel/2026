<?php

namespace Modules\ExpensesNew\Reports\Executive;

class BudgetOverrunReport
{
    public string $code = 'budgetoverrun';
    public string $title = 'BudgetOverrun Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
