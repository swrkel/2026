<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Modules\PetroPDNew\Reports\Contracts\PdnewReport;

abstract class AbstractPdnewReport implements PdnewReport
{
    protected function applyDate(Builder $query, string $column, array $filters): Builder
    {
        if (! empty($filters['date_from'])) $query->whereDate($column, '>=', $filters['date_from']);
        if (! empty($filters['date_to'])) $query->whereDate($column, '<=', $filters['date_to']);
        return $query;
    }

    protected function applyLocation(Builder $query, string $column, array $filters): Builder
    {
        if ((int) ($filters['location_id'] ?? 0) > 0) {
            $query->where($column, (int) $filters['location_id']);
        }
        return $query;
    }

    protected function applyStatus(Builder $query, string $column, array $filters): Builder
    {
        if (trim((string) ($filters['status'] ?? '')) !== '') {
            $query->where($column, $filters['status']);
        }
        return $query;
    }
}
