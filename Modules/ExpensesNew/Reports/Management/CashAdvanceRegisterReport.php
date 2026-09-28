<?php

namespace Modules\ExpensesNew\Reports\Management;

class CashAdvanceRegisterReport
{
    public string $code = 'cashadvanceregister';
    public string $title = 'CashAdvanceRegister Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
