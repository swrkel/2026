<?php

namespace Modules\ExpensesNew\Reports\Executive;

class ClaimSettlementReport
{
    public string $code = 'claimsettlement';
    public string $title = 'ClaimSettlement Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
