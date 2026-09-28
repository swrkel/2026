<?php
namespace Modules\DealerManagement\Services;

use Illuminate\Support\Facades\DB;

class HubNotificationService
{
    public function refreshReorderAlerts(int $hubDealerId): int
    {
        return app(HubDatabaseManager::class)->central(function() use($hubDealerId){
            DB::table('dlr_hub_notifications')->where('hub_dealer_id',$hubDealerId)->where('type','reorder')->where('is_read',0)->delete();
            $rows=DB::table('dlr_hub_product_sources')->where('hub_dealer_id',$hubDealerId)->where('is_active',1)
                ->select('product_key','product_name',DB::raw('SUM(source_qty) total_qty'),DB::raw('SUM(reorder_level) reorder_level'))
                ->groupBy('product_key','product_name')->havingRaw('SUM(reorder_level) > 0 AND SUM(source_qty) <= SUM(reorder_level)')->get();
            $now=now();$inserts=[];
            foreach($rows as $r){$suggested=max(0,(float)$r->reorder_level-(float)$r->total_qty);$inserts[]=['hub_dealer_id'=>$hubDealerId,'hub_user_id'=>null,'distributor_id'=>null,'type'=>'reorder','severity'=>(float)$r->total_qty<=0?'danger':'warning','title'=>'Re-order: '.$r->product_name,'message'=>'Current stock '.$r->total_qty.' is at/below re-order level '.$r->reorder_level.'. Suggested quantity: '.$suggested.'.','action_url'=>'/dealer-hub/reorder','is_read'=>0,'created_at'=>$now,'updated_at'=>$now];}
            if($inserts)DB::table('dlr_hub_notifications')->insert($inserts);return count($inserts);
        });
    }
}
