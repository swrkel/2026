<?php

namespace Modules\ExpensesNew\Reports\Management;

class WithholdingReportReport
{
    public string $code = 'withholdingreport';
    public string $title = 'WithholdingReport Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
