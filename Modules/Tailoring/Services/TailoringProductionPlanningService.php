<?php

namespace Modules\\Tailoring\\Services;

use Illuminate\Support\Facades\DB;

class TailoringProductionPlanningService
{
    public function dashboardSummary(?int $businessId = null, ?int $locationId = null): array
    {
        $query = DB::table('tailoring_job_cards');
        if ($businessId) { $query->where('business_id', $businessId); }
        if ($locationId) { $query->where('location_id', $locationId); }

        $rows = $query->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status')->toArray();
        return [
            'pending_cutting' => (int)($rows['pending_cutting'] ?? 0),
            'pending_stitching' => (int)($rows['pending_stitching'] ?? 0),
            'pending_trial' => (int)($rows['pending_trial'] ?? 0),
            'pending_alteration' => (int)($rows['pending_alteration'] ?? 0),
            'pending_ironing' => (int)($rows['pending_ironing'] ?? 0),
            'pending_qc' => (int)($rows['pending_qc'] ?? 0),
            'ready_for_delivery' => (int)($rows['ready_for_delivery'] ?? 0),
            'delivered' => (int)($rows['delivered'] ?? 0),
        ];
    }

    public function defaultStages(): array
    {
        return ['measurement','pattern_making','fabric_issue','cutting','stitching','trial','alteration','finishing','ironing','quality_control','packing','ready_for_delivery','delivered'];
    }
}
