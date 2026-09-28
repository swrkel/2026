<?php

namespace Modules\ExpensesNew\Reports\Executive;

class PettyCashSettlementReport
{
    public string $code = 'pettycashsettlement';
    public string $title = 'PettyCashSettlement Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
