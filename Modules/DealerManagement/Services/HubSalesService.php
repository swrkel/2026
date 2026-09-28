<?php
namespace Modules\DealerManagement\Services;

use Illuminate\Support\Facades\DB;

class HubSalesService
{
    public function post(int $hubDealerId, int $hubOutletId, int $hubUserId, string $saleDate, array $lines, ?string $notes=null): int
    {
        $dbm=app(HubDatabaseManager::class);
        return $dbm->central(function() use($dbm,$hubDealerId,$hubOutletId,$hubUserId,$saleDate,$lines,$notes){
                $now=now(); $entryNo='HSALE'.$now->format('YmdHis').random_int(10,99);
                $entryId=DB::table('dlr_hub_sales_entries')->insertGetId([
                    'hub_dealer_id'=>$hubDealerId,'hub_outlet_id'=>$hubOutletId,'entry_no'=>$entryNo,'sale_date'=>$saleDate,'status'=>'posted',
                    'notes'=>$notes,'submitted_by'=>$hubUserId,'submitted_at'=>$now,'created_at'=>$now,'updated_at'=>$now,
                ]);
                foreach($lines as $line){
                    $sold=max(0,(float)($line['sold_qty']??0)); $ret=max(0,(float)($line['return_qty']??0)); $dam=max(0,(float)($line['damaged_qty']??0));
                    if($sold<=0 && $ret<=0 && $dam<=0) continue;
                    $sources=DB::table('dlr_hub_product_sources')->where('hub_dealer_id',$hubDealerId)->where('hub_outlet_id',$hubOutletId)->where('product_key',$line['product_key'])->where('is_active',1)->orderBy('created_at')->get();
                    if($sources->isEmpty()) continue;
                    $opening=(float)$sources->sum('source_qty'); $received=max(0,(float)($line['received_qty']??0)); $closing=max(0,$opening+$received-$sold-$ret-$dam);
                    $method=$line['allocation_method']??($sources->first()->allocation_method??'fifo');
                    $lineId=DB::table('dlr_hub_sales_entry_lines')->insertGetId([
                        'sales_entry_id'=>$entryId,'product_key'=>$line['product_key'],'product_name'=>$line['product_name'],'sku'=>$line['sku']??null,
                        'opening_qty'=>$opening,'received_qty'=>$received,'sold_qty'=>$sold,'return_qty'=>$ret,'damaged_qty'=>$dam,'closing_qty'=>$closing,
                        'allocation_method'=>$method,'notes'=>$line['notes']??null,'created_at'=>$now,'updated_at'=>$now,
                    ]);
                    $allocations=$this->allocate($sources,$sold,$ret,$dam,$method,isset($line['distributor_id'])?(int)$line['distributor_id']:null);
                    foreach($allocations as $a){
                        $allocationId=DB::table('dlr_hub_sales_allocations')->insertGetId([
                            'sales_entry_line_id'=>$lineId,'distributor_id'=>$a['source']->distributor_id,'product_source_id'=>$a['source']->id,
                            'allocated_sold_qty'=>$a['sold'],'allocated_return_qty'=>$a['return'],'allocated_damage_qty'=>$a['damage'],
                            'allocation_method'=>$method,'sync_status'=>'pending','created_at'=>$now,'updated_at'=>$now,
                        ]);
                        $outQty=$a['sold']+$a['return']+$a['damage'];
                        if($outQty>0){
                            DB::table('dlr_hub_product_sources')->where('id',$a['source']->id)->update(['source_qty'=>DB::raw('GREATEST(0, source_qty - '.(float)$outQty.')'),'updated_at'=>$now]);
                            $this->syncAllocationToTenant($dbm,$allocationId,$a['source'],$outQty,$entryNo,$hubUserId);
                        }
                    }
                }
                return $entryId;
        });
    }

    private function allocate($sources,float $sold,float $ret,float $damage,string $method,?int $manualDistributorId): array
    {
        $need=$sold+$ret+$damage; if($need<=0)return [];
        if($manualDistributorId){$sources=$sources->filter(fn($s)=>(int)$s->distributor_id===$manualDistributorId)->values();}
        if($sources->isEmpty())return [];
        $parts=[];
        if($method==='proportional'){
            $total=max(0.0001,(float)$sources->sum('source_qty')); $remaining=$need;
            foreach($sources as $i=>$s){$qty=$i===$sources->count()-1?$remaining:min($remaining,$need*((float)$s->source_qty/$total));$parts[]=['source'=>$s,'qty'=>$qty];$remaining-=$qty;}
        }else{
            $remaining=$need; foreach($sources as $s){if($remaining<=0)break;$qty=min($remaining,max(0,(float)$s->source_qty));if($qty<=0)continue;$parts[]=['source'=>$s,'qty'=>$qty];$remaining-=$qty;}
            if($remaining>0 && $sources->count()){$parts[]=['source'=>$sources->first(),'qty'=>$remaining];}
        }
        $out=[]; $soldLeft=$sold; $retLeft=$ret; $damageLeft=$damage;
        foreach($parts as $p){$q=$p['qty'];$as=min($soldLeft,$q);$q-=$as;$soldLeft-=$as;$ar=min($retLeft,$q);$q-=$ar;$retLeft-=$ar;$ad=min($damageLeft,$q);$damageLeft-=$ad;$out[]=['source'=>$p['source'],'sold'=>$as,'return'=>$ar,'damage'=>$ad];}
        return $out;
    }

    private function syncAllocationToTenant(HubDatabaseManager $dbm,int $allocationId,$source,float $outQty,string $entryNo,int $hubUserId): void
    {
        $dist=DB::table('dlr_hub_distributors')->where('id',$source->distributor_id)->first();
        if(!$dist)return;
        try{
            $dbm->runOn($dist->database_name,function() use($dist,$source,$outQty,$entryNo,$hubUserId){
                if(!DB::getSchemaBuilder()->hasTable('dlr_stock_balances')) return;
                $b=DB::table('dlr_stock_balances')->where('business_id',$dist->business_id)->where('dealer_id',$source->local_dealer_id)
                    ->where('outlet_id',$source->local_outlet_id)->where('product_id',$source->local_product_id)
                    ->where(function($q)use($source){$source->local_variation_id===null?$q->whereNull('variation_id'):$q->where('variation_id',$source->local_variation_id);})->first();
                if(!$b)return;
                $before=(float)($b->effective_qty??$b->system_qty??0); $after=max(0,$before-$outQty); $now=now();
                DB::table('dlr_stock_balances')->where('id',$b->id)->update(['system_qty'=>$after,'effective_qty'=>$after,'last_movement_at'=>$now,'updated_at'=>$now]);
                DB::table('dlr_stock_movements')->insert([
                    'business_id'=>$dist->business_id,'dealer_id'=>$source->local_dealer_id,'outlet_id'=>$source->local_outlet_id,'product_id'=>$source->local_product_id,
                    'variation_id'=>$source->local_variation_id,'product_name'=>$source->product_name,'movement_type'=>'hub_dealer_sale','direction'=>'out','qty'=>$outQty,
                    'qty_before'=>$before,'qty_after'=>$after,'reference_type'=>'dlr_hub_sales_entry','reference_no'=>$entryNo,'notes'=>'Multi-distributor Dealer Hub sale allocation',
                    'created_by_type'=>'hub_dealer_user','created_by_id'=>$hubUserId,'movement_at'=>$now,'created_at'=>$now,'updated_at'=>$now,
                ]);
            });
            DB::table('dlr_hub_sales_allocations')->where('id',$allocationId)->update(['sync_status'=>'synced','synced_at'=>now(),'updated_at'=>now()]);
        }catch(\Throwable $e){DB::table('dlr_hub_sales_allocations')->where('id',$allocationId)->update(['sync_status'=>'error','sync_error'=>substr($e->getMessage(),0,65000),'updated_at'=>now()]);}
    }
}
