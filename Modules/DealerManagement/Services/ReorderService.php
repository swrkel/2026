<?php
namespace Modules\DealerManagement\Services;

use Illuminate\Support\Facades\DB;

class ReorderService
{
    public function suggestedQty(object $balance, ?object $rule): float
    {
        if (!$rule || !(int)$rule->is_active) return 0.0;
        $current = (float)$balance->effective_qty;
        $target = (float)$rule->max_qty;
        if ($target <= 0) $target = ((float)$rule->avg_daily_usage * (int)$rule->stock_cover_days) + (float)$rule->safety_stock_qty;
        if ($current > (float)$rule->reorder_level && (float)$rule->reorder_level > 0) return 0.0;
        return max(0, round($target - $current, 4));
    }

    public function rows(int $businessId, int $dealerId, array $outletIds): array
    {
        $records = DB::table('dlr_stock_balances as b')
            ->leftJoin('dlr_reorder_rules as r', function($join){
                $join->on('r.business_id','=','b.business_id')
                    ->on('r.dealer_id','=','b.dealer_id')
                    ->on('r.outlet_id','=','b.outlet_id')
                    ->on('r.product_id','=','b.product_id')
                    ->whereRaw('((r.variation_id = b.variation_id) OR (r.variation_id IS NULL AND b.variation_id IS NULL))');
            })
            ->where('b.business_id',$businessId)->where('b.dealer_id',$dealerId)
            ->whereIn('b.outlet_id',$outletIds ?: [0])
            ->orderBy('b.product_name')
            ->select('b.*',
                'r.id as rule_id','r.min_qty','r.max_qty','r.reorder_level','r.safety_stock_qty',
                'r.stock_cover_days','r.avg_daily_usage','r.is_active as rule_is_active')
            ->get();

        return $records->map(function($x){
            $rule = null;
            if ($x->rule_id !== null) {
                $rule = (object)[
                    'id'=>$x->rule_id,'min_qty'=>$x->min_qty,'max_qty'=>$x->max_qty,
                    'reorder_level'=>$x->reorder_level,'safety_stock_qty'=>$x->safety_stock_qty,
                    'stock_cover_days'=>$x->stock_cover_days,'avg_daily_usage'=>$x->avg_daily_usage,
                    'is_active'=>$x->rule_is_active,
                ];
            }
            $suggested = $this->suggestedQty($x,$rule);
            $status='healthy';
            if ($rule && (int)$rule->is_active === 1 && (float)$x->effective_qty <= (float)$rule->reorder_level) $status='reorder';
            elseif ($rule && (int)$rule->is_active === 1 && (float)$x->effective_qty <= (float)$rule->min_qty) $status='low';
            return ['balance'=>$x,'rule'=>$rule,'suggested_qty'=>$suggested,'status'=>$status];
        })->all();
    }

    public function reorderCount(int $businessId, int $dealerId, array $outletIds): int
    {
        return (int) DB::table('dlr_stock_balances as b')
            ->join('dlr_reorder_rules as r', function($join){
                $join->on('r.business_id','=','b.business_id')->on('r.dealer_id','=','b.dealer_id')
                    ->on('r.outlet_id','=','b.outlet_id')->on('r.product_id','=','b.product_id')
                    ->whereRaw('((r.variation_id = b.variation_id) OR (r.variation_id IS NULL AND b.variation_id IS NULL))');
            })
            ->where('b.business_id',$businessId)->where('b.dealer_id',$dealerId)
            ->whereIn('b.outlet_id',$outletIds ?: [0])->where('r.is_active',1)
            ->whereRaw('COALESCE(b.effective_qty,0) <= COALESCE(r.reorder_level,0)')->count();
    }
}
