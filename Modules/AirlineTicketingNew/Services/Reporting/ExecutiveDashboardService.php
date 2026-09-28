<?php
namespace Modules\AirlineTicketingNew\Services\Reporting;

use Illuminate\Support\Facades\DB;

class ExecutiveDashboardService
{
    public function metrics(int $businessId, array $filters = []): array
    {
        $from = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['date_to'] ?? now()->toDateString();

        return [
            'sales' => (float) DB::table('atn_tickets')
                ->where('business_id', $businessId)
                ->whereBetween('issue_date', [$from, $to])
                ->sum('grand_total'),
            'collections' => (float) DB::table('atn_payments')
                ->where('business_id', $businessId)
                ->whereBetween('payment_date', [$from, $to])
                ->sum('amount'),
            'refunds' => (float) DB::table('atn_refunds')
                ->where('business_id', $businessId)
                ->whereBetween('request_date', [$from, $to])
                ->sum('refund_amount'),
            'profit' => (float) DB::table('atn_ticket_profits as p')
                ->join('atn_tickets as t', 't.id', '=', 'p.ticket_id')
                ->where('p.business_id', $businessId)
                ->whereBetween('t.issue_date', [$from, $to])
                ->sum('p.net_profit'),
            'tickets' => DB::table('atn_tickets')
                ->where('business_id', $businessId)
                ->whereBetween('issue_date', [$from, $to])
                ->count(),
            'open_tasks' => DB::table('atn_operational_tasks')
                ->where('business_id', $businessId)
                ->where('status', 'open')
                ->count(),
        ];
    }
}
