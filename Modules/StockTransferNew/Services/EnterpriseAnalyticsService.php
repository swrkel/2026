<?php

namespace Modules\StockTransferNew\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\EnterpriseAnalyticsSnapshot;
use Modules\StockTransferNew\Entities\EnterpriseAnalyticsException;

class EnterpriseAnalyticsService
{
    public function dashboard(array $filters): array
    {
        $businessId = (int) ($filters['business_id'] ?? request()->session()->get('user.business_id'));
        $from = $filters['from'] ?? Carbon::now()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? Carbon::now()->toDateString();

        return [
            'period' => compact('from', 'to'),
            'kpis' => $this->kpis($businessId, $from, $to, $filters),
            'efficiency' => $this->efficiency($businessId, $from, $to, $filters),
            'warehouse_productivity' => $this->warehouseProductivity($businessId, $from, $to, $filters),
            'vehicle_utilization' => $this->vehicleUtilization($businessId, $from, $to, $filters),
            'product_movement' => $this->productMovement($businessId, $from, $to, $filters),
            'benchmarking' => $this->benchmarking($businessId, $from, $to, $filters),
            'exceptions' => $this->exceptions($businessId, $filters),
        ];
    }

    public function createSnapshot(array $filters, ?int $userId = null): int
    {
        $data = $this->dashboard($filters);
        $businessId = (int) ($filters['business_id'] ?? request()->session()->get('user.business_id'));
        $created = 0;

        foreach ($data['kpis'] as $key => $metric) {
            EnterpriseAnalyticsSnapshot::create([
                'business_id' => $businessId,
                'location_id' => $filters['location_id'] ?? null,
                'store_id' => $filters['store_id'] ?? null,
                'period_from' => $data['period']['from'],
                'period_to' => $data['period']['to'],
                'snapshot_type' => 'dashboard',
                'metric_key' => $key,
                'metric_label' => $metric['label'],
                'metric_value' => $metric['value'],
                'metric_payload' => $metric,
                'generated_by' => $userId,
                'generated_at' => Carbon::now(),
            ]);
            $created++;
        }

        return $created;
    }

    public function detectBottlenecks(array $filters): array
    {
        $businessId = (int) ($filters['business_id'] ?? request()->session()->get('user.business_id'));
        $rows = DB::table('stn_transfers')
            ->select('id', 'transfer_no', 'from_location_id', 'to_location_id', 'status', 'created_at', 'dispatched_at', 'received_at')
            ->where('business_id', $businessId)
            ->whereIn('status', ['approved', 'dispatched', 'in_transit', 'partially_received'])
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $created = [];
        foreach ($rows as $row) {
            $ageHours = Carbon::parse($row->created_at)->diffInHours(Carbon::now());
            if ($ageHours < 48) {
                continue;
            }
            $created[] = EnterpriseAnalyticsException::firstOrCreate([
                'business_id' => $businessId,
                'transfer_id' => $row->id,
                'exception_type' => 'transfer_bottleneck',
            ], [
                'severity' => $ageHours > 96 ? 'critical' : 'warning',
                'title' => 'Transfer delay detected: ' . $row->transfer_no,
                'description' => 'Transfer has remained open for ' . $ageHours . ' hours.',
                'recommended_action' => 'Review dispatch/receiving responsibility and update the transfer status.',
                'status' => 'open',
            ]);
        }

        return $created;
    }

    public function csv(array $filters): string
    {
        $dashboard = $this->dashboard($filters);
        $lines = ['Section,Metric,Value'];
        foreach ($dashboard['kpis'] as $metric) {
            $lines[] = 'KPI,"' . str_replace('"', '""', $metric['label']) . '",' . $metric['value'];
        }
        foreach ($dashboard['efficiency'] as $metric) {
            $lines[] = 'Efficiency,"' . str_replace('"', '""', $metric['label']) . '",' . $metric['value'];
        }
        return implode("\n", $lines);
    }

    protected function kpis(int $businessId, string $from, string $to, array $filters): array
    {
        $base = $this->baseTransfers($businessId, $from, $to, $filters);
        $total = (clone $base)->count();
        $completed = (clone $base)->whereIn('status', ['received', 'completed'])->count();
        $delayed = (clone $base)->where('expected_receive_date', '<', Carbon::today()->toDateString())->whereNotIn('status', ['received', 'completed', 'cancelled'])->count();
        $variance = (clone $base)->where('variance_status', '!=', 'none')->count();

        return [
            'total_transfers' => ['label' => 'Total Transfers', 'value' => $total],
            'completion_rate' => ['label' => 'Completion Rate %', 'value' => $total ? round(($completed / $total) * 100, 2) : 0],
            'delayed_transfers' => ['label' => 'Delayed Transfers', 'value' => $delayed],
            'variance_cases' => ['label' => 'Variance Cases', 'value' => $variance],
        ];
    }

    protected function efficiency(int $businessId, string $from, string $to, array $filters): array
    {
        $avgHours = $this->baseTransfers($businessId, $from, $to, $filters)
            ->whereNotNull('dispatched_at')->whereNotNull('received_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, dispatched_at, received_at)) as avg_hours')
            ->value('avg_hours');

        return [
            ['label' => 'Average Transit Hours', 'value' => round((float) $avgHours, 2)],
            ['label' => 'Efficiency Score', 'value' => max(0, 100 - round((float) $avgHours, 2))],
        ];
    }

    protected function warehouseProductivity(int $businessId, string $from, string $to, array $filters): array
    {
        return DB::table('stn_transfers')
            ->select('from_store_id', DB::raw('COUNT(*) as transfer_count'), DB::raw('SUM(total_qty) as qty_total'))
            ->where('business_id', $businessId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->groupBy('from_store_id')
            ->orderByDesc('transfer_count')
            ->limit(20)
            ->get()
            ->toArray();
    }

    protected function vehicleUtilization(int $businessId, string $from, string $to, array $filters): array
    {
        return DB::table('stn_logistics_shipments')
            ->select('vehicle_no', DB::raw('COUNT(*) as shipment_count'), DB::raw('SUM(COALESCE(load_weight,0)) as total_weight'))
            ->where('business_id', $businessId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->groupBy('vehicle_no')
            ->orderByDesc('shipment_count')
            ->limit(20)
            ->get()
            ->toArray();
    }

    protected function productMovement(int $businessId, string $from, string $to, array $filters): array
    {
        return DB::table('stn_transfer_lines')
            ->join('stn_transfers', 'stn_transfers.id', '=', 'stn_transfer_lines.transfer_id')
            ->select('stn_transfer_lines.product_id', DB::raw('SUM(stn_transfer_lines.requested_qty) as requested_qty'), DB::raw('SUM(stn_transfer_lines.received_qty) as received_qty'))
            ->where('stn_transfers.business_id', $businessId)
            ->whereBetween(DB::raw('DATE(stn_transfers.created_at)'), [$from, $to])
            ->groupBy('stn_transfer_lines.product_id')
            ->orderByDesc('requested_qty')
            ->limit(50)
            ->get()
            ->toArray();
    }

    protected function benchmarking(int $businessId, string $from, string $to, array $filters): array
    {
        return DB::table('stn_transfers')
            ->select('from_location_id', 'to_location_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN status IN ("received","completed") THEN 1 ELSE 0 END) as completed'))
            ->where('business_id', $businessId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->groupBy('from_location_id', 'to_location_id')
            ->orderByDesc('total')
            ->limit(30)
            ->get()
            ->toArray();
    }

    protected function exceptions(int $businessId, array $filters): array
    {
        return EnterpriseAnalyticsException::where('business_id', $businessId)
            ->where('status', 'open')
            ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
            ->orderByDesc('id')
            ->limit(25)
            ->get()
            ->toArray();
    }

    protected function baseTransfers(int $businessId, string $from, string $to, array $filters)
    {
        $query = DB::table('stn_transfers')
            ->where('business_id', $businessId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]);

        foreach (['location_id' => 'from_location_id', 'store_id' => 'from_store_id'] as $filter => $column) {
            if (!empty($filters[$filter])) {
                $query->where($column, $filters[$filter]);
            }
        }

        return $query;
    }
}
