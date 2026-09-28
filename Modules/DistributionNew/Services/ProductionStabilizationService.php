<?php

namespace Modules\DistributionNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Models\DisnewProductionException;
use Modules\DistributionNew\Models\DisnewProductionAudit;

class ProductionStabilizationService
{
    public function dashboard(int $businessId, $locationId = null): array
    {
        return [
            'pending_orders' => $this->safeCount('disnew_sales_orders', $businessId, ['status' => 'pending']),
            'orders_awaiting_loading' => $this->safeCount('disnew_sales_orders', $businessId, ['status' => 'approved']),
            'vehicles_on_route' => $this->safeCount('disnew_vehicle_trips', $businessId, ['status' => 'dispatched']),
            'pending_collections' => $this->safeCount('disnew_collections', $businessId, ['status' => 'pending']),
            'pending_returns' => $this->safeCount('disnew_returns', $businessId, ['status' => 'pending']),
            'open_exceptions' => DisnewProductionException::where('business_id', $businessId)->where('status', 'open')->count(),
            'failed_sms' => $this->safeCount('disnew_sms_logs', $businessId, ['status' => 'failed']),
            'offline_sync_pending' => $this->safeCount('disnew_offline_sync_queue', $businessId, ['status' => 'pending']),
        ];
    }

    public function exceptions(int $businessId, array $filters = [])
    {
        $query = DisnewProductionException::where('business_id', $businessId)->latest();
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['type'])) {
            $query->where('exception_type', $filters['type']);
        }
        return $query->paginate(25);
    }

    public function audits(int $businessId, array $filters = [])
    {
        $query = DisnewProductionAudit::where('business_id', $businessId)->latest();
        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }
        return $query->paginate(25);
    }

    public function resolveException(int $businessId, int $id, ?string $note): void
    {
        DisnewProductionException::where('business_id', $businessId)->where('id', $id)->update([
            'status' => 'resolved',
            'resolution_note' => $note,
            'resolved_at' => now(),
        ]);
    }

    protected function safeCount(string $table, int $businessId, array $where = []): int
    {
        if (!DB::getSchemaBuilder()->hasTable($table)) {
            return 0;
        }
        $q = DB::table($table)->where('business_id', $businessId);
        foreach ($where as $k => $v) {
            $q->where($k, $v);
        }
        return (int) $q->count();
    }
}
