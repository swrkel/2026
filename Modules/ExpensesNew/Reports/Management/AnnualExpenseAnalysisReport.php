<?php

namespace Modules\ExpensesNew\Reports\Management;

class AnnualExpenseAnalysisReport
{
    public string $code = 'annualexpenseanalysis';
    public string $title = 'AnnualExpenseAnalysis Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
