<?php

namespace Modules\ExpensesNew\Reports\Executive;

class YearEndAnalysisReport
{
    public string $code = 'yearendanalysis';
    public string $title = 'YearEndAnalysis Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
