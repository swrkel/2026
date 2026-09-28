<?php

namespace Modules\ExpensesNew\Reports\Financial;

class PaymentMethodAnalysisReport
{
    public string $code = 'paymentmethodanalysis';
    public string $title = 'PaymentMethodAnalysis Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
