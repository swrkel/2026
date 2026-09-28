<?php

namespace Modules\ExpensesNew\Reports\Executive;

class RecurringDueReport
{
    public string $code = 'recurringdue';
    public string $title = 'RecurringDue Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
