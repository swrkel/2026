<?php

namespace Modules\ExpensesNew\Reports\Management;

class BusinessWiseExpensesReport
{
    public string $code = 'businesswiseexpenses';
    public string $title = 'BusinessWiseExpenses Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
