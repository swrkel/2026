<?php
namespace Modules\DealerManagement\Services\Integration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\DealerManagement\Services\StockService;

class DistributionNewAdapter
{
    public function syncBusiness(int $businessId): array
    {
        $result = ['deliveries'=>0,'returns'=>0,'skipped'=>0,'errors'=>[]];
        if (!Schema::hasTable('disnew_deliveries')) return $result;
        $deliveries = DB::table('disnew_deliveries')->where('business_id',$businessId)->where('status','delivered')->orderBy('id')->get();
        foreach ($deliveries as $delivery) {
            $key = 'distribution_delivery:'.$businessId.':'.$delivery->id;
            if ($this->processed($key)) { $result['skipped']++; continue; }
            try { $this->syncDelivery($delivery,$key); $result['deliveries']++; } catch (\Throwable $e) { $result['errors'][]=$e->getMessage(); }
        }
        if (Schema::hasTable('disnew_returns')) {
            $returns = DB::table('disnew_returns')->where('business_id',$businessId)->whereIn('status',['approved','completed'])->orderBy('id')->get();
            foreach ($returns as $return) {
                $key = 'distribution_return:'.$businessId.':'.$return->id;
                if ($this->processed($key)) { $result['skipped']++; continue; }
                try { $this->syncReturn($return,$key); $result['returns']++; } catch (\Throwable $e) { $result['errors'][]=$e->getMessage(); }
            }
        }
        return $result;
    }

    private function syncDelivery(object $delivery,string $key): void
    {
        DB::transaction(function() use($delivery,$key){
            $dealer = DB::table('dlr_dealers')->where('business_id',$delivery->business_id)->where('customer_id',$delivery->customer_id)->where('status','active')->first();
            if (!$dealer) { $this->record($delivery->business_id,$key,'delivery',$delivery->id,'skipped',['reason'=>'dealer_not_mapped']); return; }
            $outlet = DB::table('dlr_outlets')->where('dealer_id',$dealer->id)->where('is_default',1)->first()
                ?: DB::table('dlr_outlets')->where('dealer_id',$dealer->id)->where('is_active',1)->first();
            if (!$outlet) throw new \RuntimeException('Dealer '.$dealer->name.' has no active outlet.');
            $lines = DB::table('disnew_delivery_lines')->where('disnew_delivery_id',$delivery->id)->get();
            foreach($lines as $line) {
                $qty = max(0,(float)$line->delivered_qty);
                if ($qty <= 0) continue;
                app(StockService::class)->applyMovement([
                    'business_id'=>$dealer->business_id,'dealer_id'=>$dealer->id,'outlet_id'=>$outlet->id,'product_id'=>$line->product_id,
                    'variation_id'=>$line->variation_id,'movement_type'=>'distribution_delivery','direction'=>'in','qty'=>$qty,
                    'reference_type'=>'disnew_delivery','reference_id'=>$delivery->id,'reference_no'=>$delivery->delivery_no,
                    'created_by_type'=>'integration','movement_at'=>$delivery->delivered_at ?: now(),
                ]);
            }
            $this->record($delivery->business_id,$key,'delivery',$delivery->id,'processed',['dealer_id'=>$dealer->id]);
        });
    }

    private function syncReturn(object $return,string $key): void
    {
        DB::transaction(function() use($return,$key){
            $dealer = DB::table('dlr_dealers')->where('business_id',$return->business_id)->where('customer_id',$return->customer_id)->where('status','active')->first();
            if (!$dealer) { $this->record($return->business_id,$key,'return',$return->id,'skipped',['reason'=>'dealer_not_mapped']); return; }
            $outlet = DB::table('dlr_outlets')->where('dealer_id',$dealer->id)->where('is_default',1)->first()
                ?: DB::table('dlr_outlets')->where('dealer_id',$dealer->id)->where('is_active',1)->first();
            if (!$outlet) throw new \RuntimeException('Dealer '.$dealer->name.' has no active outlet.');
            foreach(DB::table('disnew_return_lines')->where('return_id',$return->id)->get() as $line) {
                if ((float)$line->qty <= 0) continue;
                app(StockService::class)->applyMovement([
                    'business_id'=>$dealer->business_id,'dealer_id'=>$dealer->id,'outlet_id'=>$outlet->id,'product_id'=>$line->product_id,
                    'variation_id'=>$line->variation_id,'movement_type'=>'distribution_return','direction'=>'out','qty'=>(float)$line->qty,
                    'reference_type'=>'disnew_return','reference_id'=>$return->id,'reference_no'=>$return->return_no,'created_by_type'=>'integration',
                ]);
            }
            $this->record($return->business_id,$key,'return',$return->id,'processed',['dealer_id'=>$dealer->id]);
        });
    }

    private function processed(string $key): bool { return DB::table('dlr_integration_events')->where('event_key',$key)->exists(); }
    private function record(int $businessId,string $key,string $type,int $sourceId,string $status,array $payload=[]): void
    {
        DB::table('dlr_integration_events')->insert([
            'business_id'=>$businessId,'event_key'=>$key,'source_module'=>'DistributionNew','source_type'=>$type,'source_id'=>$sourceId,
            'status'=>$status,'payload_json'=>json_encode($payload),'processed_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
    }
}
