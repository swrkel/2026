<?php
namespace Modules\DealerManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockService
{
    public function applyMovement(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $variationId = $data['variation_id'] ?? null;
            $balance = DB::table('dlr_stock_balances')
                ->where('business_id',$data['business_id'])->where('dealer_id',$data['dealer_id'])
                ->where('outlet_id',$data['outlet_id'])->where('product_id',$data['product_id'])
                ->where(function($q) use ($variationId){ $variationId === null ? $q->whereNull('variation_id') : $q->where('variation_id',$variationId); })
                ->lockForUpdate()->first();
            $before = $balance ? (float)$balance->effective_qty : 0.0;
            $direction = $data['direction'];
            $qty = (float)$data['qty'];
            $after = $direction === 'in' ? $before + $qty : ($direction === 'out' ? $before - $qty : $qty);
            $now = now();
            if ($balance) {
                DB::table('dlr_stock_balances')->where('id',$balance->id)->update([
                    'system_qty'=>$after,'effective_qty'=>$after,
                    'confirmed_qty'=>$direction === 'set' ? $after : $balance->confirmed_qty,
                    'last_confirmed_at'=>$direction === 'set' ? $now : $balance->last_confirmed_at,
                    'last_movement_at'=>$now,'updated_at'=>$now,
                ]);
                $balanceId = $balance->id;
            } else {
                $balanceId = DB::table('dlr_stock_balances')->insertGetId([
                    'business_id'=>$data['business_id'],'dealer_id'=>$data['dealer_id'],'outlet_id'=>$data['outlet_id'],
                    'product_id'=>$data['product_id'],'variation_id'=>$variationId,'product_name'=>$data['product_name'] ?? $this->productName($data['product_id']),
                    'system_qty'=>$after,'confirmed_qty'=>$direction === 'set' ? $after : null,'effective_qty'=>$after,
                    'last_confirmed_at'=>$direction === 'set' ? $now : null,'last_movement_at'=>$now,'created_at'=>$now,'updated_at'=>$now,
                ]);
            }
            DB::table('dlr_stock_movements')->insert([
                'business_id'=>$data['business_id'],'dealer_id'=>$data['dealer_id'],'outlet_id'=>$data['outlet_id'],
                'product_id'=>$data['product_id'],'variation_id'=>$variationId,'product_name'=>$data['product_name'] ?? $this->productName($data['product_id']),
                'movement_type'=>$data['movement_type'],'direction'=>$direction,'qty'=>$qty,'qty_before'=>$before,'qty_after'=>$after,
                'reference_type'=>$data['reference_type'] ?? null,'reference_id'=>$data['reference_id'] ?? null,'reference_no'=>$data['reference_no'] ?? null,
                'notes'=>$data['notes'] ?? null,'created_by_type'=>$data['created_by_type'] ?? 'system','created_by_id'=>$data['created_by_id'] ?? null,
                'movement_at'=>$data['movement_at'] ?? $now,'created_at'=>$now,'updated_at'=>$now,
            ]);
            return $balanceId;
        });
    }

    public function productName(int $productId): ?string
    {
        if (Schema::hasTable('products')) {
            $row = DB::table('products')->where('id',$productId)->first();
            return $row->name ?? null;
        }
        return null;
    }
}
