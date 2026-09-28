<?php

namespace Modules\ExpensesNew\Reports\Management;

class VatReportReport
{
    public string $code = 'vatreport';
    public string $title = 'VatReport Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
