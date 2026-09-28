<?php

namespace Modules\ExpensesNew\Reports\Financial;

class VarianceAnalysisReport
{
    public string $code = 'varianceanalysis';
    public string $title = 'VarianceAnalysis Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
