<?php

namespace Modules\ExpensesNew\Reports\Operational;

class PettyCashRegisterReport
{
    public string $code = 'pettycashregister';
    public string $title = 'PettyCashRegister Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
