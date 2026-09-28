<?php
namespace Modules\AirlineTicketingNew\Services\Performance;

use Illuminate\Database\Query\Builder;

class QueryOptimizationService
{
    public function applyBusinessScope(Builder $query, int $businessId, ?int $locationId = null, ?int $storeId = null): Builder
    {
        return $query
            ->where($query->from . '.business_id', $businessId)
            ->when($locationId, fn ($q) => $q->where($query->from . '.business_location_id', $locationId))
            ->when($storeId, fn ($q) => $q->where($query->from . '.store_id', $storeId));
    }

    public function applyDateRange(Builder $query, string $column, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate($column, '>=', $from))
            ->when($to, fn ($q) => $q->whereDate($column, '<=', $to));
    }
}
