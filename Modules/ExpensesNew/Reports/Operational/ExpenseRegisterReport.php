<?php

namespace Modules\ExpensesNew\Reports\Operational;

class ExpenseRegisterReport
{
    public string $code = 'expenseregister';
    public string $title = 'ExpenseRegister Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
