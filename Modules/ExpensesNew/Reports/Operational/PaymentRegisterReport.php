<?php

namespace Modules\ExpensesNew\Reports\Operational;

class PaymentRegisterReport
{
    public string $code = 'paymentregister';
    public string $title = 'PaymentRegister Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
