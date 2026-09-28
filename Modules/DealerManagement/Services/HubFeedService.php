<?php
namespace Modules\DealerManagement\Services;

use Illuminate\Support\Facades\DB;

class HubFeedService
{
    public function refresh(int $hubDealerId): array
    {
        $dbm=app(HubDatabaseManager::class);$counts=['deliveries'=>0,'returns'=>0,'errors'=>[]];
        app(HubProductService::class)->syncDealerProducts($hubDealerId);
        app(HubNotificationService::class)->refreshReorderAlerts($hubDealerId);
        return $dbm->central(function() use($hubDealerId,$dbm,$counts){
            $connections=DB::table('dlr_hub_connections as c')->join('dlr_hub_distributors as d','d.id','=','c.distributor_id')->where('c.hub_dealer_id',$hubDealerId)->where('c.status','active')->select('c.*','d.database_name','d.business_id')->get();
            foreach($connections as $c){try{$moves=$dbm->runOn($c->database_name,fn()=>DB::table('dlr_stock_movements')->where('business_id',$c->business_id)->where('dealer_id',$c->local_dealer_id)->whereIn('movement_type',['delivery','return','sales_return','dealer_return'])->orderByDesc('id')->limit(1000)->get());foreach($moves as $m){$baseRef=$m->reference_no?:'MOV';$ref=$baseRef.'#'.$m->id;$payload=json_encode(['product_name'=>$m->product_name,'qty'=>$m->qty,'movement_type'=>$m->movement_type,'reference_no'=>$baseRef]);if($m->movement_type==='delivery'){DB::table('dlr_hub_delivery_feed')->updateOrInsert(['distributor_id'=>$c->distributor_id,'reference_no'=>$ref],['hub_dealer_id'=>$hubDealerId,'hub_outlet_id'=>null,'delivery_at'=>$m->movement_at,'status'=>'delivered','payload_json'=>$payload,'updated_at'=>now(),'created_at'=>now()]);$counts['deliveries']++;}else{DB::table('dlr_hub_return_feed')->updateOrInsert(['distributor_id'=>$c->distributor_id,'reference_no'=>$ref],['hub_dealer_id'=>$hubDealerId,'hub_outlet_id'=>null,'return_at'=>$m->movement_at,'status'=>'processed','payload_json'=>$payload,'updated_at'=>now(),'created_at'=>now()]);$counts['returns']++;}}}catch(\Throwable $e){$counts['errors'][]=$e->getMessage();}}
            return $counts;
        });
    }
}
