<?php

namespace Modules\BeautySaloons\Services\Reports;

use Illuminate\Http\Request;

class BeautyReportFilterService
{
    public function filters(Request $request): array
    {
        return [
            'start_date' => $request->input('start_date') ?: now()->startOfMonth()->toDateString(),
            'end_date' => $request->input('end_date') ?: now()->toDateString(),
            'business_location_id' => $request->input('business_location_id'),
            'staff_id' => $request->input('staff_id'),
            'customer_id' => $request->input('customer_id'),
            'status' => $request->input('status'),
        ];
    }

    public function applyDate($query, array $filters, string $column)
    {
        if (!empty($filters['start_date'])) {
            $query->whereDate($column, '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate($column, '<=', $filters['end_date']);
        }
        return $query;
    }
}
