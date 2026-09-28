<?php
namespace Modules\AirlineTicketingNew\Services\Dashboard;
use Illuminate\Support\Facades\DB;
class ManagementDashboardService {
    public function metrics(int $b): array {
        return [
            'today_sales'=>(float)DB::table('atn_tickets')->where('business_id',$b)->whereDate('issue_date',today())->sum('grand_total'),
            'today_collections'=>(float)DB::table('atn_payments')->where('business_id',$b)->whereDate('payment_date',today())->sum('amount'),
            'today_refunds'=>(float)DB::table('atn_refunds')->where('business_id',$b)->whereDate('request_date',today())->sum('refund_amount'),
            'today_tickets'=>DB::table('atn_tickets')->where('business_id',$b)->whereDate('issue_date',today())->count(),
            'receivables'=>(float)DB::table('atn_invoices')->where('business_id',$b)->sum('due_total'),
            'payables'=>(float)DB::table('atn_supplier_settlements')->where('business_id',$b)->sum('due_amount'),
            'bsp_due'=>(float)DB::table('atn_bsp_remittances')->where('business_id',$b)->sum('due_amount'),
            'net_profit'=>(float)DB::table('atn_ticket_profits')->where('business_id',$b)->sum('net_profit'),
        ];
    }
}
