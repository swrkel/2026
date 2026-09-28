<?php

namespace Modules\StockTransferNew\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\OperationsException;

class OperationsControlCenterService
{
    public function dashboard(array $filters = []): array
    {
        return [
            'filters' => $filters,
            'kpis' => $this->kpis($filters),
            'live_status' => $this->liveBoard($filters),
            'exceptions' => $this->exceptions($filters)['exceptions'],
            'workload' => $this->workload($filters)['workload'],
        ];
    }

    public function liveBoard(array $filters = []): array
    {
        $rows = DB::table('stn_transfers as t')
            ->select('t.id', 't.transfer_no', 't.status', 't.priority', 't.from_location_id', 't.to_location_id', 't.from_store_id', 't.to_store_id', 't.expected_delivery_at', 't.updated_at')
            ->when(!empty($filters['business_id']), fn ($q) => $q->where('t.business_id', $filters['business_id']))
            ->when(!empty($filters['location_id']), fn ($q) => $q->where(function ($qq) use ($filters) {
                $qq->where('t.from_location_id', $filters['location_id'])->orWhere('t.to_location_id', $filters['location_id']);
            }))
            ->when(!empty($filters['store_id']), fn ($q) => $q->where(function ($qq) use ($filters) {
                $qq->where('t.from_store_id', $filters['store_id'])->orWhere('t.to_store_id', $filters['store_id']);
            }))
            ->whereIn('t.status', ['draft', 'pending_approval', 'approved', 'dispatched', 'in_transit', 'partially_received'])
            ->orderByRaw("FIELD(t.priority, 'critical', 'urgent', 'normal')")
            ->orderBy('t.expected_delivery_at')
            ->limit(100)
            ->get();

        return ['rows' => $rows, 'generated_at' => Carbon::now()->toDateTimeString()];
    }

    public function exceptions(array $filters = []): array
    {
        $exceptions = OperationsException::query()
            ->when(!empty($filters['exception_type']), fn ($q) => $q->where('exception_type', $filters['exception_type']))
            ->when(!empty($filters['date_from']), fn ($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->latest()
            ->paginate(50);

        return ['filters' => $filters, 'exceptions' => $exceptions];
    }

    public function workload(array $filters = []): array
    {
        $workload = DB::table('stn_transfers')
            ->select('to_location_id', 'to_store_id', DB::raw('count(*) as transfer_count'), DB::raw('sum(total_qty) as total_qty'))
            ->when(!empty($filters['business_id']), fn ($q) => $q->where('business_id', $filters['business_id']))
            ->whereIn('status', ['approved', 'dispatched', 'in_transit', 'partially_received'])
            ->groupBy('to_location_id', 'to_store_id')
            ->orderByDesc('transfer_count')
            ->get();

        return ['filters' => $filters, 'workload' => $workload];
    }

    public function calendar(array $filters = []): array
    {
        $events = DB::table('stn_transfers')
            ->select('id', 'transfer_no', 'priority', 'status', 'expected_dispatch_at', 'expected_delivery_at')
            ->whereNotNull('expected_dispatch_at')
            ->orderBy('expected_dispatch_at')
            ->limit(250)
            ->get();

        return ['filters' => $filters, 'events' => $events];
    }

    public function escalate(int $transferId, ?string $remarks): void
    {
        OperationsException::create([
            'transfer_id' => $transferId,
            'exception_type' => 'manual_escalation',
            'severity' => 'high',
            'remarks' => $remarks,
            'status' => 'open',
            'created_by' => auth()->id(),
        ]);
    }

    public function exportCsv(array $filters = []): string
    {
        $rows = $this->liveBoard($filters)['rows'];
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Transfer No', 'Status', 'Priority', 'From Location', 'To Location', 'Expected Delivery', 'Updated At']);
        foreach ($rows as $row) {
            fputcsv($out, [$row->transfer_no, $row->status, $row->priority, $row->from_location_id, $row->to_location_id, $row->expected_delivery_at, $row->updated_at]);
        }
        rewind($out);
        return stream_get_contents($out) ?: '';
    }

    protected function kpis(array $filters = []): array
    {
        $base = DB::table('stn_transfers')
            ->when(!empty($filters['business_id']), fn ($q) => $q->where('business_id', $filters['business_id']));

        return [
            'pending_approval' => (clone $base)->where('status', 'pending_approval')->count(),
            'in_transit' => (clone $base)->whereIn('status', ['dispatched', 'in_transit', 'partially_received'])->count(),
            'sla_breached' => (clone $base)->whereNotNull('expected_delivery_at')->where('expected_delivery_at', '<', Carbon::now())->whereNotIn('status', ['received', 'cancelled'])->count(),
            'critical' => (clone $base)->where('priority', 'critical')->whereNotIn('status', ['received', 'cancelled'])->count(),
        ];
    }
}
