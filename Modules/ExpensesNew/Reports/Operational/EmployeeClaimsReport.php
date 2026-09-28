<?php

namespace Modules\ExpensesNew\Reports\Operational;

class EmployeeClaimsReport
{
    public string $code = 'employeeclaims';
    public string $title = 'EmployeeClaims Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
