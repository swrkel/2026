<?php
namespace Modules\DealerManagement\Services;

use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function refresh(int $businessId, int $dealerId, array $outletIds): void
    {
        $days = (int)config('dealermanagement.stale_stock_days',3);
        $rows = app(ReorderService::class)->rows($businessId,$dealerId,$outletIds);
        if (!$rows) return;

        $balanceIds = array_values(array_filter(array_map(fn($r)=>(int)$r['balance']->id,$rows)));
        $existing = DB::table('dlr_notifications')
            ->where('business_id',$businessId)->where('dealer_id',$dealerId)->where('is_read',0)
            ->where('reference_type','stock_balance')->whereIn('reference_id',$balanceIds)
            ->whereIn('type',['reorder_level','stock_stale'])
            ->get(['type','reference_id'])
            ->mapWithKeys(fn($x)=>[$x->type.':'.$x->reference_id=>true])->all();

        $now=now(); $inserts=[];
        foreach($rows as $row){
            $b=$row['balance'];
            if($row['status']==='reorder' && empty($existing['reorder_level:'.$b->id])){
                $inserts[]=['business_id'=>$businessId,'dealer_id'=>$dealerId,'outlet_id'=>$b->outlet_id,'type'=>'reorder_level','severity'=>'warning','title'=>'Re-order level reached','message'=>($b->product_name ?: 'Product #'.$b->product_id).' balance is '.number_format((float)$b->effective_qty,2).'. Suggested quantity: '.number_format((float)$row['suggested_qty'],2).'.','action_url'=>route('dealermanagement.portal.stock.index'),'reference_type'=>'stock_balance','reference_id'=>$b->id,'is_read'=>0,'created_at'=>$now,'updated_at'=>$now];
            }
            $stale = !$b->last_confirmed_at || $now->diffInDays($b->last_confirmed_at) >= $days;
            if($stale && empty($existing['stock_stale:'.$b->id])){
                $inserts[]=['business_id'=>$businessId,'dealer_id'=>$dealerId,'outlet_id'=>$b->outlet_id,'type'=>'stock_stale','severity'=>'info','title'=>'Stock confirmation due','message'=>($b->product_name ?: 'Product #'.$b->product_id).' has not been physically confirmed recently.','action_url'=>route('dealermanagement.portal.stock.update.form'),'reference_type'=>'stock_balance','reference_id'=>$b->id,'is_read'=>0,'created_at'=>$now,'updated_at'=>$now];
            }
        }
        if($inserts) DB::table('dlr_notifications')->insert($inserts);
    }
}
