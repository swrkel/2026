<?php

namespace Modules\LeadsNew\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeadsNewExecutiveDashboardService
{
    public function summary(array $filters = []): array
    {
        $from = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : now()->startOfMonth();
        $to = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : now()->endOfDay();

        $leadQuery = DB::table('leads_new_leads')->whereBetween('created_at', [$from, $to]);
        $oppQuery = DB::table('leads_new_opportunities')->whereBetween('created_at', [$from, $to]);

        if (!empty($filters['business_id'])) {
            $leadQuery->where('business_id', $filters['business_id']);
            $oppQuery->where('business_id', $filters['business_id']);
        }
        if (!empty($filters['location_id'])) {
            $leadQuery->where('location_id', $filters['location_id']);
            $oppQuery->where('location_id', $filters['location_id']);
        }

        $totalLeads = (clone $leadQuery)->count();
        $converted = (clone $leadQuery)->where('status_key', 'converted')->count();
        $lost = (clone $leadQuery)->where('status_key', 'lost')->count();

        return [
            'total_leads' => $totalLeads,
            'converted_leads' => $converted,
            'lost_leads' => $lost,
            'open_leads' => max($totalLeads - $converted - $lost, 0),
            'conversion_rate' => $totalLeads > 0 ? round(($converted / $totalLeads) * 100, 2) : 0,
            'pipeline_value' => (clone $oppQuery)->sum('expected_value'),
            'won_value' => (clone $oppQuery)->where('status', 'won')->sum('expected_value'),
            'lost_value' => (clone $oppQuery)->where('status', 'lost')->sum('expected_value'),
            'followups_due' => DB::table('leads_new_followups')->whereDate('followup_at', '<=', now()->toDateString())->whereIn('status', ['pending','overdue'])->count(),
        ];
    }

    public function funnel(array $filters = []): array
    {
        $rows = DB::table('leads_new_leads')
            ->select('status_key', DB::raw('COUNT(*) as total'))
            ->groupBy('status_key')
            ->orderBy('total', 'desc')
            ->get();

        return $rows->map(fn($row) => ['stage' => $row->status_key ?: 'new', 'total' => (int) $row->total])->toArray();
    }
}
