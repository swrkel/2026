<?php

namespace Modules\ExpensesNew\Reports\Financial;

class DuplicateExpenseReport
{
    public string $code = 'duplicateexpense';
    public string $title = 'DuplicateExpense Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
