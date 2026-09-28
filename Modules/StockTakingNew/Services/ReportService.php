<?php

namespace Modules\StockTakingNew\Services;

use Modules\StockTakingNew\Entities\StockTakeAuditLog;
use Modules\StockTakingNew\Entities\StockTakeLine;
use Modules\StockTakingNew\Entities\StockTakeSession;

class ReportService
{
    public function sessionQuery(int $businessId, array $filters = [])
    {
        return StockTakeSession::query()
            ->where('business_id', $businessId)
            ->when($this->hasRestrictedLocations($filters), fn ($query) => $query->whereIn('location_id', $filters['_permitted_location_ids'] ?: [-1]))
            ->when(! empty($filters['location_id']), fn ($query) => $query->where('location_id', $filters['location_id']))
            ->when(array_key_exists('store_id', $filters) && $filters['store_id'] !== '', fn ($query) => $query->where('store_id', $filters['store_id']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('count_date', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('count_date', '<=', $filters['date_to']));
    }

    public function varianceQuery(int $businessId, array $filters = [])
    {
        return StockTakeLine::query()
            ->join('stk_sessions as s', 's.id', '=', 'stk_session_lines.session_id')
            ->where('stk_session_lines.business_id', $businessId)
            ->whereNull('s.deleted_at')
            ->when($this->hasRestrictedLocations($filters), fn ($query) => $query->whereIn('s.location_id', $filters['_permitted_location_ids'] ?: [-1]))
            ->when(! empty($filters['session_id']), fn ($query) => $query->where('stk_session_lines.session_id', $filters['session_id']))
            ->when(! empty($filters['location_id']), fn ($query) => $query->where('s.location_id', $filters['location_id']))
            ->when(array_key_exists('store_id', $filters) && $filters['store_id'] !== '', fn ($query) => $query->where('s.store_id', $filters['store_id']))
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('s.count_date', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('s.count_date', '<=', $filters['date_to']))
            ->when(($filters['variance_only'] ?? '1') === '1', fn ($query) => $query->where('stk_session_lines.variance_qty', '<>', 0))
            ->select('stk_session_lines.*', 's.stock_take_no', 's.count_date', 's.status as session_status');
    }

    public function auditQuery(int $businessId, array $filters = [])
    {
        $query = StockTakeAuditLog::where('business_id', $businessId)
            ->when(! empty($filters['session_id']), fn ($builder) => $builder->where('session_id', $filters['session_id']))
            ->when(! empty($filters['event']), fn ($builder) => $builder->where('event', $filters['event']))
            ->when(! empty($filters['date_from']), fn ($builder) => $builder->whereDate('created_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($builder) => $builder->whereDate('created_at', '<=', $filters['date_to']));

        if ($this->hasRestrictedLocations($filters)) {
            $sessionIds = StockTakeSession::where('business_id', $businessId)
                ->whereIn('location_id', $filters['_permitted_location_ids'] ?: [-1])
                ->select('id');
            $query->where(function ($nested) use ($sessionIds): void {
                $nested->whereNull('session_id')->orWhereIn('session_id', $sessionIds);
            });
        }

        return $query;
    }

    private function hasRestrictedLocations(array $filters): bool
    {
        return array_key_exists('_permitted_location_ids', $filters)
            && is_array($filters['_permitted_location_ids']);
    }
}
