<?php

namespace Modules\StockTransferNew\Services\Admin;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransferLogisticsExecutionService
{
    public function dashboard(array $filters = []): array
    {
        $loads = $this->loadQuery($filters);
        $summary = DB::query()->fromSub($loads, 'x')
            ->selectRaw('COUNT(*) as total_loads')
            ->selectRaw("SUM(CASE WHEN load_status = 'planned' THEN 1 ELSE 0 END) as planned_loads")
            ->selectRaw("SUM(CASE WHEN load_status = 'dispatched' THEN 1 ELSE 0 END) as dispatched_loads")
            ->selectRaw("SUM(CASE WHEN load_status = 'received' THEN 1 ELSE 0 END) as received_loads")
            ->selectRaw("SUM(CASE WHEN eta_at IS NOT NULL AND eta_at < NOW() AND load_status NOT IN ('received','cancelled') THEN 1 ELSE 0 END) as delayed_loads")
            ->selectRaw('COALESCE(SUM(loaded_qty),0) as loaded_qty')
            ->selectRaw('COALESCE(SUM(vehicle_capacity_qty),0) as capacity_qty')
            ->first();

        return [
            'summary' => [
                'total_loads' => (int) ($summary->total_loads ?? 0),
                'planned_loads' => (int) ($summary->planned_loads ?? 0),
                'dispatched_loads' => (int) ($summary->dispatched_loads ?? 0),
                'received_loads' => (int) ($summary->received_loads ?? 0),
                'delayed_loads' => (int) ($summary->delayed_loads ?? 0),
                'loaded_qty' => (float) ($summary->loaded_qty ?? 0),
                'capacity_qty' => (float) ($summary->capacity_qty ?? 0),
            ],
            'loads' => $this->loadQuery($filters)->orderByDesc('vl.id')->limit(300)->get(),
            'consolidations' => $this->consolidationQuery($filters)->orderByDesc('c.id')->limit(100)->get(),
        ];
    }

    public function createLoad(array $data, int $userId): int
    {
        if (empty($data['transfer_id'])) {
            throw new RuntimeException('Transfer ID is required to create a vehicle load.');
        }

        return DB::transaction(function () use ($data, $userId) {
            $capacity = (float) ($data['vehicle_capacity_qty'] ?? 0);
            $loaded = (float) ($data['loaded_qty'] ?? 0);
            $loadId = DB::table('stock_transfer_new_vehicle_loads')->insertGetId([
                'load_no' => $data['load_no'] ?? $this->nextNo('LOAD', 'stock_transfer_new_vehicle_loads', 'load_no'),
                'transfer_id' => (int) $data['transfer_id'],
                'transfer_no' => $data['transfer_no'] ?? null,
                'route_id' => $data['route_id'] ?? null,
                'vehicle_no' => $data['vehicle_no'] ?? null,
                'driver_name' => $data['driver_name'] ?? null,
                'driver_mobile' => $data['driver_mobile'] ?? null,
                'assistant_name' => $data['assistant_name'] ?? null,
                'planned_dispatch_at' => $data['planned_dispatch_at'] ?? null,
                'eta_at' => $data['eta_at'] ?? null,
                'vehicle_capacity_qty' => $capacity,
                'loaded_qty' => $loaded,
                'capacity_variance_qty' => $capacity - $loaded,
                'load_status' => 'planned',
                'gps_reference' => $data['gps_reference'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->log($loadId, 'load_created', $userId, 'Vehicle load created.');
            return $loadId;
        });
    }

    public function dispatchLoad(int $loadId, array $checklist, int $userId): void
    {
        DB::transaction(function () use ($loadId, $checklist, $userId) {
            $load = DB::table('stock_transfer_new_vehicle_loads')->where('id', $loadId)->lockForUpdate()->first();
            if (! $load) {
                throw new RuntimeException('Vehicle load not found.');
            }
            if (! in_array($load->load_status, ['planned'], true)) {
                throw new RuntimeException('Only planned loads can be dispatched.');
            }
            DB::table('stock_transfer_new_vehicle_loads')->where('id', $loadId)->update([
                'load_status' => 'dispatched',
                'actual_dispatch_at' => now(),
                'dispatch_checklist_json' => json_encode($checklist),
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
            $this->log($loadId, 'load_dispatched', $userId, 'Vehicle load dispatched.');
        });
    }

    public function receiveLoad(int $loadId, array $checklist, int $userId): void
    {
        DB::transaction(function () use ($loadId, $checklist, $userId) {
            $load = DB::table('stock_transfer_new_vehicle_loads')->where('id', $loadId)->lockForUpdate()->first();
            if (! $load) {
                throw new RuntimeException('Vehicle load not found.');
            }
            if (! in_array($load->load_status, ['dispatched'], true)) {
                throw new RuntimeException('Only dispatched loads can be received.');
            }
            DB::table('stock_transfer_new_vehicle_loads')->where('id', $loadId)->update([
                'load_status' => 'received',
                'received_at' => now(),
                'receiving_checklist_json' => json_encode($checklist),
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
            $this->log($loadId, 'load_received', $userId, 'Vehicle load received.');
        });
    }

    public function createConsolidation(array $transferIds, array $scope, int $userId): int
    {
        if (count($transferIds) < 1) {
            throw new RuntimeException('Select at least one transfer for consolidation.');
        }
        return DB::transaction(function () use ($transferIds, $scope, $userId) {
            $headerId = DB::table('stock_transfer_new_consolidations')->insertGetId([
                'consolidation_no' => $this->nextNo('CON', 'stock_transfer_new_consolidations', 'consolidation_no'),
                'consolidation_date' => now()->toDateString(),
                'from_business_id' => $scope['from_business_id'] ?? null,
                'from_location_id' => $scope['from_location_id'] ?? null,
                'from_store_id' => $scope['from_store_id'] ?? null,
                'to_business_id' => $scope['to_business_id'] ?? null,
                'to_location_id' => $scope['to_location_id'] ?? null,
                'to_store_id' => $scope['to_store_id'] ?? null,
                'transfer_count' => count($transferIds),
                'consolidation_status' => 'draft',
                'remarks' => $scope['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ($transferIds as $transferId) {
                DB::table('stock_transfer_new_consolidation_lines')->insertOrIgnore([
                    'consolidation_id' => $headerId,
                    'transfer_id' => (int) $transferId,
                    'transfer_no' => $scope['transfer_no_'.$transferId] ?? null,
                    'transfer_status' => $scope['transfer_status_'.$transferId] ?? null,
                    'transfer_qty' => (float) ($scope['transfer_qty_'.$transferId] ?? 0),
                    'transfer_value' => (float) ($scope['transfer_value_'.$transferId] ?? 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return $headerId;
        });
    }

    public function export(array $filters = [])
    {
        return $this->loadQuery($filters)->orderByDesc('vl.id')->get();
    }

    protected function loadQuery(array $filters)
    {
        $query = DB::table('stock_transfer_new_vehicle_loads as vl')
            ->leftJoin('stock_transfer_new_routes as r', 'r.id', '=', 'vl.route_id')
            ->select('vl.*', 'r.route_code', 'r.route_name');
        foreach (['load_status', 'vehicle_no', 'driver_name'] as $field) {
            if (! empty($filters[$field])) {
                $query->where('vl.'.$field, 'like', '%'.$filters[$field].'%');
            }
        }
        if (! empty($filters['date_from'])) $query->whereDate('vl.planned_dispatch_at', '>=', $filters['date_from']);
        if (! empty($filters['date_to'])) $query->whereDate('vl.planned_dispatch_at', '<=', $filters['date_to']);
        return $query;
    }

    protected function consolidationQuery(array $filters)
    {
        return DB::table('stock_transfer_new_consolidations as c')->select('c.*');
    }

    protected function nextNo(string $prefix, string $table, string $column): string
    {
        $id = (int) DB::table($table)->max('id') + 1;
        return $prefix . '-' . now()->format('Ymd') . '-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }

    protected function log(int $loadId, string $type, int $userId, string $remarks): void
    {
        if (! DB::getSchemaBuilder()->hasTable('stock_transfer_new_activity_logs')) return;
        DB::table('stock_transfer_new_activity_logs')->insert([
            'reference_type' => 'vehicle_load',
            'reference_id' => $loadId,
            'action_type' => $type,
            'remarks' => $remarks,
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
