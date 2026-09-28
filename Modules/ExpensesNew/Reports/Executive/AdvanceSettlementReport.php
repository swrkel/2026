<?php

namespace Modules\ExpensesNew\Reports\Executive;

class AdvanceSettlementReport
{
    public string $code = 'advancesettlement';
    public string $title = 'AdvanceSettlement Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
