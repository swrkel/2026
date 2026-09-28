<?php

namespace Modules\ExpensesNew\Reports\Operational;

class CategoryAnalysisReport
{
    public string $code = 'categoryanalysis';
    public string $title = 'CategoryAnalysis Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
