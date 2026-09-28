<?php

namespace Modules\ExpensesNew\Reports\Management;

class DepartmentSpendingReport
{
    public string $code = 'departmentspending';
    public string $title = 'DepartmentSpending Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
