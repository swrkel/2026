<?php

namespace Modules\ExpensesNew\Reports\Financial;

class CashFlowImpactReport
{
    public string $code = 'cashflowimpact';
    public string $title = 'CashFlowImpact Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
