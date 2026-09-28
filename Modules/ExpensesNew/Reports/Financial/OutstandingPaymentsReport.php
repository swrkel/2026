<?php

namespace Modules\ExpensesNew\Reports\Financial;

class OutstandingPaymentsReport
{
    public string $code = 'outstandingpayments';
    public string $title = 'OutstandingPayments Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
