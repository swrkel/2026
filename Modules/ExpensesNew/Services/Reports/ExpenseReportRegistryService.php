<?php

namespace Modules\ExpensesNew\Services\Reports;

class ExpenseReportRegistryService
{
    public function all($user = null): array
    {
        return config('expensesnew_reports.reports', []);
    }

    public function find(string $code): array
    {
        return config('expensesnew_reports.reports.' . $code, ['code' => $code, 'title' => ucfirst(str_replace('_', ' ', $code))]);
    }

    public function run(string $code, array $filters = []): array
    {
        return [
            'code' => $code,
            'filters' => $filters,
            'rows' => [],
            'totals' => [],
        ];
    }

    public function datatable(string $code, array $request = []): array
    {
        return [
            'draw' => (int)($request['draw'] ?? 1),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
        ];
    }
}
