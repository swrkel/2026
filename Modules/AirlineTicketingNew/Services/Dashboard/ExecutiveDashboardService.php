<?php

namespace Modules\AirlineTicketingNew\Services\Dashboard;

use Illuminate\Support\Facades\DB;

class ExecutiveDashboardService
{
    public function summary(int $businessId): array
    {
        return [
            'today_sales' => (float) DB::table('atn_tickets')->where('business_id',$businessId)->whereDate('issue_date',today())->sum('grand_total'),
            'today_payments' => (float) DB::table('atn_payments')->where('business_id',$businessId)->whereDate('payment_date',today())->sum('amount'),
            'open_reservations' => DB::table('atn_reservations')->where('business_id',$businessId)->whereIn('status',['reserved','confirmed','on_hold'])->count(),
            'pending_refunds' => DB::table('atn_refunds')->where('business_id',$businessId)->whereIn('status',['pending','approved'])->count(),
            'supplier_due' => (float) DB::table('atn_supplier_settlements')->where('business_id',$businessId)->sum('due_amount'),
            'net_profit' => (float) DB::table('atn_ticket_profits')->where('business_id',$businessId)->sum('net_profit'),
        ];
    }
}
