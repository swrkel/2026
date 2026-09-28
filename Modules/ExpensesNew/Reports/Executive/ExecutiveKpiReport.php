<?php

namespace Modules\ExpensesNew\Reports\Executive;

class ExecutiveKpiReport
{
    public string $code = 'executivekpi';
    public string $title = 'ExecutiveKpi Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
