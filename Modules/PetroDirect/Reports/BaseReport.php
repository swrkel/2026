<?php

namespace Modules\PetroDirect\Reports;

use Illuminate\Support\Facades\DB;

abstract class BaseReport
{
    protected function businessId(?int $businessId = null): int
    {
        $businessId = $businessId
            ?? (request()->hasSession() ? request()->session()->get('business.id') : null)
            ?? (request()->hasSession() ? request()->session()->get('user.business_id') : null)
            ?? optional(auth()->user())->business_id;

        if (empty($businessId)) {
            abort(403, 'Business context not found. Please logout and login again.');
        }

        return (int) $businessId;
    }

    protected function applyDateRange($query, string $column = 'created_at', ?string $startDate = null, ?string $endDate = null)
    {
        if (! empty($startDate) && ! empty($endDate)) {
            $query->whereBetween(DB::raw('DATE(' . $column . ')'), [$startDate, $endDate]);
        }

        return $query;
    }

    protected function applyLocation($query, string $column = 'location_id', $locationId = null)
    {
        if (! empty($locationId) && $locationId !== 'all') {
            $query->where($column, $locationId);
        }

        return $query;
    }
}
