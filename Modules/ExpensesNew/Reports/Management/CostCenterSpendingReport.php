<?php

namespace Modules\ExpensesNew\Reports\Management;

class CostCenterSpendingReport
{
    public string $code = 'costcenterspending';
    public string $title = 'CostCenterSpending Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
