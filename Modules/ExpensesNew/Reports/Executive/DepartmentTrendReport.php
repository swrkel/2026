<?php

namespace Modules\ExpensesNew\Reports\Executive;

class DepartmentTrendReport
{
    public string $code = 'departmenttrend';
    public string $title = 'DepartmentTrend Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
