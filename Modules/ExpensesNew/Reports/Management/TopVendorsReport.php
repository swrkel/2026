<?php

namespace Modules\ExpensesNew\Reports\Management;

class TopVendorsReport
{
    public string $code = 'topvendors';
    public string $title = 'TopVendors Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
