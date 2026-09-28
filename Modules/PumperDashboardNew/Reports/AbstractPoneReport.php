<?php

namespace Modules\PumperDashboardNew\Reports;

use Illuminate\Database\Eloquent\Builder;
use Modules\PumperDashboardNew\Utils\PoneDateRange;

abstract class AbstractPoneReport implements PoneReport
{
    protected function applyScope(
        Builder $query,
        int $businessId,
        array $filters,
        string $dateColumn
    ): Builder {
        $query->where('business_id', $businessId);

        if (! empty($filters['location_id'])) {
            $query->where('location_id', (int) $filters['location_id']);
        }
        if (! empty($filters['operator_profile_id'])) {
            $query->where('operator_profile_id', (int) $filters['operator_profile_id']);
        }

        [$from, $to] = PoneDateRange::fromFilters($filters);
        if ($from) {
            $query->where($dateColumn, '>=', $from);
        }
        if ($to) {
            $query->where($dateColumn, '<=', $to);
        }

        return $query;
    }

    protected function limit(array $filters): int
    {
        return min(5000, max(25, (int) ($filters['limit'] ?? 500)));
    }
}
