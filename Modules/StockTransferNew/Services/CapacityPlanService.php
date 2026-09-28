<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CapacityPlanService
{
    public function plans(array $filters)
    {
        $query = DB::table('stn_transfers as t')
            ->leftJoin('stn_transfer_shipments as s', 's.transfer_id', '=', 't.id')
            ->select('t.id','t.transfer_no','t.priority','t.status','t.business_id','t.from_location_id','t.to_location_id','s.vehicle_id','s.eta_at','s.capacity_used','s.capacity_limit')
            ->orderByDesc('t.id');

        if (!empty($filters['business_id'])) $query->where('t.business_id', $filters['business_id']);
        if (!empty($filters['location_id'])) $query->where(function($q) use ($filters) { $q->where('t.from_location_id',$filters['location_id'])->orWhere('t.to_location_id',$filters['location_id']); });
        if (!empty($filters['vehicle_id'])) $query->where('s.vehicle_id', $filters['vehicle_id']);
        if (!empty($filters['priority'])) $query->where('t.priority', $filters['priority']);
        if (!empty($filters['from_date'])) $query->whereDate('t.created_at', '>=', $filters['from_date']);
        if (!empty($filters['to_date'])) $query->whereDate('t.created_at', '<=', $filters['to_date']);

        return $query->paginate(30);
    }

    public function summary(array $filters): array
    {
        $rows = collect($this->plans($filters)->items());
        return [
            'total_transfers' => $rows->count(),
            'over_capacity' => $rows->filter(fn($r) => (float)($r->capacity_used ?? 0) > (float)($r->capacity_limit ?? 0))->count(),
            'critical' => $rows->where('priority', 'critical')->count(),
            'unassigned_vehicle' => $rows->filter(fn($r) => empty($r->vehicle_id))->count(),
        ];
    }

    public function exportCsv(array $filters): StreamedResponse
    {
        $rows = $this->plans($filters);
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Transfer No','Priority','Status','Vehicle','ETA','Capacity Used','Capacity Limit']);
            foreach ($rows as $row) {
                fputcsv($out, [$row->transfer_no,$row->priority,$row->status,$row->vehicle_id,$row->eta_at,$row->capacity_used,$row->capacity_limit]);
            }
            fclose($out);
        }, 'stn_capacity_plan.csv');
    }
}
