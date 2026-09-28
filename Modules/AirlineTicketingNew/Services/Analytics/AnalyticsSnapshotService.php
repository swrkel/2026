<?php
namespace Modules\AirlineTicketingNew\Services\Analytics;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\AnalyticsSnapshot;

class AnalyticsSnapshotService
{
    public function create(int $businessId, string $date): AnalyticsSnapshot
    {
        $metrics = [
            'sales' => (float) DB::table('atn_tickets')
                ->where('business_id', $businessId)
                ->whereDate('issue_date', $date)
                ->sum('grand_total'),
            'tickets' => DB::table('atn_tickets')
                ->where('business_id', $businessId)
                ->whereDate('issue_date', $date)
                ->count(),
            'refunds' => (float) DB::table('atn_refunds')
                ->where('business_id', $businessId)
                ->whereDate('request_date', $date)
                ->sum('refund_amount'),
            'collections' => (float) DB::table('atn_payments')
                ->where('business_id', $businessId)
                ->whereDate('payment_date', $date)
                ->sum('amount'),
        ];

        return AnalyticsSnapshot::query()->updateOrCreate(
            ['business_id' => $businessId, 'snapshot_date' => $date],
            ['metrics_json' => $metrics]
        );
    }
}
