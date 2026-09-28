<?php

namespace Modules\ExpensesNew\Reports\Management;

class BudgetVsActualReport
{
    public string $code = 'budgetvsactual';
    public string $title = 'BudgetVsActual Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
