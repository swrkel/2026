<?php

namespace Modules\ExpensesNew\Services\Reports;

class ExpenseScheduledReportService
{
    public function businessId($request): ?int
    {
        return $request->business_id ?? session('user.business_id') ?? null;
    }

    public function locationId($request): ?int
    {
        return $request->location_id ?? session('business_location_id') ?? null;
    }

    public function summary(array $filters = []): array
    {
        return [
            'status' => 'ready',
            'filters' => $filters,
            'module' => 'Expenses-New',
            'standalone' => true,
        ];
    }
}
