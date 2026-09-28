<?php

namespace Modules\ExpensesNew\Reports\Executive;

class MonthlyClosingReport
{
    public string $code = 'monthlyclosing';
    public string $title = 'MonthlyClosing Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
