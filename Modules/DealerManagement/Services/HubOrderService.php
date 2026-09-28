<?php
namespace Modules\DealerManagement\Services;

use Illuminate\Support\Facades\DB;

class HubOrderService
{
    public function createAndSplit(int $hubDealerId,int $hubOutletId,int $hubUserId,array $lines,?string $deliveryDate=null,?string $notes=null): int
    {
        $dbm=app(HubDatabaseManager::class);
        return $dbm->central(function() use($dbm,$hubDealerId,$hubOutletId,$hubUserId,$lines,$deliveryDate,$notes){
                $now=now();$no='HORD'.$now->format('YmdHis').random_int(10,99);
                $id=DB::table('dlr_hub_orders')->insertGetId(['hub_dealer_id'=>$hubDealerId,'hub_outlet_id'=>$hubOutletId,'hub_order_no'=>$no,'order_date'=>today(),'requested_delivery_date'=>$deliveryDate,'status'=>'submitted','notes'=>$notes,'submitted_by'=>$hubUserId,'created_at'=>$now,'updated_at'=>$now]);
                $groups=[];
                foreach($lines as $line){$qty=max(0,(float)($line['requested_qty']??0));if($qty<=0)continue;$source=$this->chooseSource($hubDealerId,$hubOutletId,$line['product_key'],isset($line['preferred_distributor_id'])?(int)$line['preferred_distributor_id']:null);if(!$source)continue;DB::table('dlr_hub_order_lines')->insert(['hub_order_id'=>$id,'product_key'=>$line['product_key'],'product_name'=>$line['product_name'],'preferred_distributor_id'=>$source->distributor_id,'current_qty'=>$line['current_qty']??0,'suggested_qty'=>$line['suggested_qty']??0,'requested_qty'=>$qty,'created_at'=>$now,'updated_at'=>$now]);$groups[$source->distributor_id][]=['source'=>$source,'qty'=>$qty,'name'=>$line['product_name']];}
                foreach($groups as $distId=>$group)$this->createTenantOrder($dbm,$id,(int)$distId,$group,$deliveryDate,$notes,$hubUserId);
                return $id;
        });
    }

    private function chooseSource(int $dealerId,int $outletId,string $productKey,?int $preferred)
    {
        $q=DB::table('dlr_hub_product_sources')->where('hub_dealer_id',$dealerId)->where('hub_outlet_id',$outletId)->where('product_key',$productKey)->where('is_active',1);
        if($preferred)$q->where('distributor_id',$preferred);
        return $q->orderByDesc('source_qty')->first();
    }

    private function createTenantOrder(HubDatabaseManager $dbm,int $hubOrderId,int $distId,array $group,?string $deliveryDate,?string $notes,int $hubUserId): void
    {
        $dist=DB::table('dlr_hub_distributors')->where('id',$distId)->first();if(!$dist)return;
        try{
            $result=$dbm->runOn($dist->database_name,function() use($hubOrderId,$dist,$group,$deliveryDate,$notes,$hubUserId){
                $source=$group[0]['source'];$now=now();$no='DH'.now()->format('ymdHis').random_int(10,99);
                $orderId=DB::table('dlr_orders')->insertGetId(['business_id'=>$dist->business_id,'dealer_id'=>$source->local_dealer_id,'outlet_id'=>$source->local_outlet_id,'order_no'=>$no,'order_date'=>today(),'requested_delivery_date'=>$deliveryDate,'status'=>'submitted','source'=>'dealer_hub','notes'=>$notes,'submitted_by'=>null,'submitted_at'=>$now,'created_at'=>$now,'updated_at'=>$now]);
                $rows=[];foreach($group as $g){$s=$g['source'];$rows[]=['order_id'=>$orderId,'product_id'=>$s->local_product_id,'variation_id'=>$s->local_variation_id,'product_name'=>$g['name'],'current_qty'=>$s->source_qty,'suggested_qty'=>0,'requested_qty'=>$g['qty'],'notes'=>'Hub order #'.$hubOrderId,'created_at'=>$now,'updated_at'=>$now];}if($rows)DB::table('dlr_order_lines')->insert($rows);
                DB::table('dlr_integration_outbox')->insert(['business_id'=>$dist->business_id,'dealer_id'=>$source->local_dealer_id,'event_type'=>'dealer_hub_order_submitted','aggregate_type'=>'dlr_order','aggregate_id'=>$orderId,'payload_json'=>json_encode(['hub_order_id'=>$hubOrderId,'order_no'=>$no]),'status'=>'pending','created_at'=>$now,'updated_at'=>$now]);
                return [$orderId,$no];
            });
            DB::table('dlr_hub_split_orders')->updateOrInsert(['hub_order_id'=>$hubOrderId,'distributor_id'=>$distId],['local_order_id'=>$result[0],'local_order_no'=>$result[1],'status'=>'synced','synced_at'=>now(),'updated_at'=>now(),'created_at'=>now()]);
        }catch(\Throwable $e){DB::table('dlr_hub_split_orders')->updateOrInsert(['hub_order_id'=>$hubOrderId,'distributor_id'=>$distId],['status'=>'error','sync_error'=>substr($e->getMessage(),0,65000),'updated_at'=>now(),'created_at'=>now()]);}
    }
}
