<?php
namespace Modules\AirlineTicketingNew\Services\Analytics;

use Illuminate\Support\Facades\DB;

class BusinessIntelligenceService
{
    public function monthlySales(int $businessId, int $months = 12): array
    {
        return DB::table('atn_tickets')
            ->where('business_id', $businessId)
            ->whereDate('issue_date', '>=', now()->subMonths($months)->startOfMonth())
            ->groupByRaw("DATE_FORMAT(issue_date, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(issue_date, '%Y-%m')")
            ->selectRaw("DATE_FORMAT(issue_date, '%Y-%m') period, SUM(grand_total) value")
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function yearOverYear(int $businessId): array
    {
        $current = (float) DB::table('atn_tickets')
            ->where('business_id', $businessId)
            ->whereYear('issue_date', now()->year)
            ->sum('grand_total');

        $previous = (float) DB::table('atn_tickets')
            ->where('business_id', $businessId)
            ->whereYear('issue_date', now()->subYear()->year)
            ->sum('grand_total');

        return [
            'current' => round($current, 4),
            'previous' => round($previous, 4),
            'change_percent' => $previous == 0 ? null : round((($current - $previous) / $previous) * 100, 2),
        ];
    }
}
