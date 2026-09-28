<?php
namespace Modules\DealerManagement\Services;

use Illuminate\Support\Facades\DB;

class HubProductService
{
    public function syncDealerProducts(int $hubDealerId): array
    {
        $dbm=app(HubDatabaseManager::class);
        return $dbm->central(function() use($hubDealerId,$dbm) {
            $connections=DB::table('dlr_hub_connections as c')->join('dlr_hub_distributors as d','d.id','=','c.distributor_id')
                ->where('c.hub_dealer_id',$hubDealerId)->where('c.status','active')
                ->select('c.*','d.database_name','d.business_id','d.name as distributor_name')->get();
            $outlets=DB::table('dlr_hub_outlets')->where('hub_dealer_id',$hubDealerId)->where('is_active',1)->get();
            $synced=0; $errors=[];
            foreach($connections as $c){
                try{
                    $rows=$dbm->runOn($c->database_name,function() use($c){
                        if(!DB::getSchemaBuilder()->hasTable('dlr_stock_balances')) return collect();
                        $q=DB::table('dlr_stock_balances as b')->leftJoin('dlr_outlets as o','o.id','=','b.outlet_id');
                        $cols=['b.outlet_id','b.product_id','b.variation_id','b.product_name','b.effective_qty','b.system_qty','b.confirmed_qty','o.hub_outlet_id'];
                        if(DB::getSchemaBuilder()->hasTable('products')) {$q->leftJoin('products as p','p.id','=','b.product_id');$cols[]=DB::raw("COALESCE(p.sku, '') as sku");}
                        else {$cols[]=DB::raw("'' as sku");}
                        return $q->where('b.business_id',$c->business_id)->where('b.dealer_id',$c->local_dealer_id)->select($cols)->get();
                    });
                    foreach($rows as $r){
                        $hubOutlet=!empty($r->hub_outlet_id) ? $outlets->firstWhere('id',(int)$r->hub_outlet_id) : $outlets->first();
                        if(!$hubOutlet) continue;
                        $key=$this->productKey($r->product_name,$r->sku??null);
                        DB::table('dlr_hub_product_sources')->updateOrInsert([
                            'hub_dealer_id'=>$hubDealerId,'hub_outlet_id'=>$hubOutlet->id,'distributor_id'=>$c->distributor_id,
                            'local_product_id'=>$r->product_id,'local_variation_id'=>$r->variation_id,
                        ],[
                            'local_dealer_id'=>$c->local_dealer_id,'local_outlet_id'=>$r->outlet_id,'product_key'=>$key,'product_name'=>$r->product_name,'sku'=>$r->sku??null,
                            'source_qty'=>(float)($r->effective_qty ?? $r->confirmed_qty ?? $r->system_qty ?? 0),'is_active'=>1,'last_synced_at'=>now(),'updated_at'=>now(),'created_at'=>now()
                        ]); $synced++;
                    }
                }catch(\Throwable $e){$errors[]=$c->distributor_name.': '.$e->getMessage();}
            }
            return ['synced'=>$synced,'errors'=>$errors];
        });
    }

    private function productKey(?string $name,?string $sku=null): string
    {
        $sku=trim((string)$sku);
        if($sku!=='') return sha1('sku|'.strtolower($sku));
        $base=strtolower(trim(preg_replace('/\s+/',' ',(string)$name)));
        return sha1('name|'.$base);
    }

    public function consolidatedStock(int $hubDealerId, array $outletIds=[], ?int $distributorId=null)
    {
        return app(HubDatabaseManager::class)->central(function() use($hubDealerId,$outletIds,$distributorId){
            $q=DB::table('dlr_hub_product_sources as s')->join('dlr_hub_distributors as d','d.id','=','s.distributor_id')
                ->where('s.hub_dealer_id',$hubDealerId)->where('s.is_active',1);
            if($outletIds)$q->whereIn('s.hub_outlet_id',$outletIds); if($distributorId)$q->where('s.distributor_id',$distributorId);
            return $q->select('s.product_key','s.product_name','s.sku',DB::raw('SUM(s.source_qty) as total_qty'),DB::raw('SUM(s.reorder_level) as total_reorder_level'),DB::raw('COUNT(DISTINCT s.distributor_id) as distributor_count'),DB::raw('GREATEST(SUM(s.reorder_level) - SUM(s.source_qty), 0) as suggested_qty'))
                ->groupBy('s.product_key','s.product_name','s.sku')->orderBy('s.product_name')->get();
        });
    }
}
