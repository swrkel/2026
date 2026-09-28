<?php
namespace Modules\AirlineTicketingNew\Services\Bsp;
use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\BspPeriod;
class BspReconciliationService {
    public function summary(BspPeriod $p): array {
        $sales=(float)DB::table('atn_tickets')->where('business_id',$p->business_id)->whereBetween('issue_date',[$p->period_from,$p->period_to])->where('status','issued')->sum('grand_total');
        $refunds=(float)DB::table('atn_refunds')->where('business_id',$p->business_id)->whereBetween('request_date',[$p->period_from,$p->period_to])->sum('refund_amount');
        $adjustments=(float)DB::table('atn_bsp_adjustments')->where('business_id',$p->business_id)->whereBetween('adjustment_date',[$p->period_from,$p->period_to])->sum('amount');
        return ['sales'=>round($sales,4),'refunds'=>round($refunds,4),'adjustments'=>round($adjustments,4),'net'=>round($sales-$refunds+$adjustments,4)];
    }
}
