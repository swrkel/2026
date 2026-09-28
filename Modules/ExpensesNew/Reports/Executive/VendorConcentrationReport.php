<?php

namespace Modules\ExpensesNew\Reports\Executive;

class VendorConcentrationReport
{
    public string $code = 'vendorconcentration';
    public string $title = 'VendorConcentration Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
