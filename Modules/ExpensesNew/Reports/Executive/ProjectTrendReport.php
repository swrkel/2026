<?php

namespace Modules\ExpensesNew\Reports\Executive;

class ProjectTrendReport
{
    public string $code = 'projecttrend';
    public string $title = 'ProjectTrend Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
