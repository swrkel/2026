<?php

namespace Modules\ExpensesNew\Reports\Executive;

class CostCenterTrendReport
{
    public string $code = 'costcentertrend';
    public string $title = 'CostCenterTrend Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
