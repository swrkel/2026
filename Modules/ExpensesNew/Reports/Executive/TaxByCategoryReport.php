<?php

namespace Modules\ExpensesNew\Reports\Executive;

class TaxByCategoryReport
{
    public string $code = 'taxbycategory';
    public string $title = 'TaxByCategory Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
