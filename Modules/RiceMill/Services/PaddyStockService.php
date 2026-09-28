<?php
namespace Modules\RiceMill\Services;

use Illuminate\Support\Facades\DB;
use Modules\RiceMill\Models\PaddyLot;
use Modules\RiceMill\Models\PaddyStockMovement;

class PaddyStockService
{
    public function move(int $businessId, int $lotId, string $type, float $qty, array $meta=[]): PaddyStockMovement
    {
        if ($qty <= 0) throw new \InvalidArgumentException('Quantity must be greater than zero.');
        return DB::transaction(function() use($businessId,$lotId,$type,$qty,$meta){
            $lot=PaddyLot::forBusiness($businessId)->lockForUpdate()->findOrFail($lotId);
            $sign=$this->sign($type);
            $new=(float)$lot->balance_qty + ($sign*$qty);
            if ($new < -0.0005) throw new \RuntimeException('Insufficient paddy stock in lot '.$lot->lot_no.'.');
            $m=PaddyStockMovement::create([
                'business_id'=>$businessId,
                'location_id'=>$meta['location_id']??$lot->location_id,
                'store_id'=>$meta['store_id']??$lot->store_id,
                'paddy_lot_id'=>$lotId,
                'movement_date'=>$meta['movement_date']??now()->toDateString(),
                'movement_type'=>$type,
                'quantity'=>$qty,
                'signed_quantity'=>$sign*$qty,
                'reference_type'=>$meta['reference_type']??null,
                'reference_id'=>$meta['reference_id']??null,
                'note'=>$meta['note']??null,
                'created_by'=>$meta['created_by']??null,
            ]);
            $lot->update(['balance_qty'=>$new]);
            return $m;
        });
    }

    /**
     * Post several stock movements with one lot-lock query and one movement
     * insert. Used by Production completion to remove the old N+1 stock pattern.
     *
     * Each item: lot_id, type, qty and optional meta.
     */
    public function moveMany(int $businessId, array $moves): void
    {
        if (! $moves) {
            return;
        }

        DB::transaction(function () use ($businessId, $moves) {
            $lotIds=array_values(array_unique(array_map(static fn($m)=>(int)$m['lot_id'],$moves)));
            $lots=PaddyLot::forBusiness($businessId)
                ->whereIn('id',$lotIds)
                ->lockForUpdate()
                ->get(['id','business_id','lot_no','balance_qty','location_id','store_id'])
                ->keyBy('id');
            abort_unless($lots->count()===count($lotIds),422,'One of the selected Paddy Lots is no longer available.');

            $running=[];
            foreach($lots as $lot){
                $running[(int)$lot->id]=(float)$lot->balance_qty;
            }

            $rows=[];
            $now=now();
            foreach($moves as $move){
                $lotId=(int)$move['lot_id'];
                $qty=(float)$move['qty'];
                if($qty<=0) throw new \InvalidArgumentException('Quantity must be greater than zero.');
                $type=(string)$move['type'];
                $meta=(array)($move['meta']??[]);
                $sign=$this->sign($type);
                $running[$lotId]+=$sign*$qty;
                $lot=$lots->get($lotId);
                if($running[$lotId] < -0.0005){
                    throw new \RuntimeException('Insufficient paddy stock in lot '.$lot->lot_no.'.');
                }
                $rows[]=[
                    'business_id'=>$businessId,
                    'location_id'=>$meta['location_id']??$lot->location_id,
                    'store_id'=>$meta['store_id']??$lot->store_id,
                    'paddy_lot_id'=>$lotId,
                    'movement_date'=>$meta['movement_date']??now()->toDateString(),
                    'movement_type'=>$type,
                    'quantity'=>$qty,
                    'signed_quantity'=>$sign*$qty,
                    'reference_type'=>$meta['reference_type']??null,
                    'reference_id'=>$meta['reference_id']??null,
                    'note'=>$meta['note']??null,
                    'created_by'=>$meta['created_by']??null,
                    'created_at'=>$now,
                    'updated_at'=>$now,
                ];
            }

            foreach($running as $lotId=>$balance){
                DB::table('rcm_paddy_lots')
                    ->where('business_id',$businessId)
                    ->where('id',$lotId)
                    ->update(['balance_qty'=>$balance,'updated_at'=>$now]);
            }
            if($rows){
                DB::table('rcm_paddy_stock_movements')->insert($rows);
            }
        });
    }

    private function sign(string $type): int
    {
        return in_array($type,['receipt','transfer_in','adjustment_in','return_in'],true) ? 1 : -1;
    }
}
