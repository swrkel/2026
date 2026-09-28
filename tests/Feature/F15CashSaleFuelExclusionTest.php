<?php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\MPCS\Services\FormHelper;
use Illuminate\Support\Facades\DB;
use App\Category;

class F15CashSaleFuelExclusionTest extends TestCase
{
    /**
     * Test if F15 cash today excludes fuel category sales.
     *
     * @return void
     */
    public function test_f15_cash_today_excludes_fuel_logic()
    {
        $business_id = 1;
        $date = '2025-01-01';
        
        // 1. Get value from the NEW implementation in FormHelper
        $newCash = FormHelper::getCashTodayExcludingFuel($business_id, $date);
        
        // 2. Get value from manual logic (the one we want to match)
        $expectedCash = $this->getF9ALogicExcludingFuel($business_id, $date);
        
        // This should PASS now because we implemented the logic in FormHelper
        $this->assertEquals($expectedCash, $newCash, 'The new FormHelper method should match the F9A logic excluding Fuel');
    }
    
    private function getF9ALogicExcludingFuel($business_id, $date)
    {
        $nonFuelSubCategoryIds = DB::table('categories as c')
            ->leftJoin('categories as pc', 'c.parent_id', '=', 'pc.id')
            ->where('c.business_id', $business_id)
            ->where('c.parent_id', '!=', 0)
            ->where('pc.name', '!=', 'Fuel')
            ->pluck('c.id');
            
        if ($nonFuelSubCategoryIds->isEmpty()) {
            return 0.0;
        }

        $totalCash = 0.0;
        foreach ($nonFuelSubCategoryIds as $catId) {
            $today_sales = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->whereNull('t.deleted_at')
                ->whereNull('tsl.deleted_at')
                ->where('p.sub_category_id', $catId)
                ->whereDate('t.transaction_date', $date)
                ->selectRaw("
                    SUM(tsl.unit_price_inc_tax * tsl.quantity) as income,
                    SUM(CASE
                        WHEN (t.is_credit_sale = 1
                              OR t.payment_status IN ('due','partial')
                              OR EXISTS (
                                  SELECT 1 FROM transaction_payments tp2
                                  WHERE tp2.transaction_id = t.id
                                    AND tp2.method IN ('credit', 'credit_sale')
                                    AND tp2.deleted_at IS NULL
                              ))
                             AND NOT EXISTS (
                                 SELECT 1 FROM settlement_credit_sale_payments scsp_lk
                                 WHERE scsp_lk.transaction_id = t.id
                             )
                        THEN tsl.unit_price_inc_tax * tsl.quantity
                        ELSE 0
                    END) as credit
                ")
                ->first();

            $petro_credit_today = (float) DB::table('settlement_credit_sale_payments as scsp')
                ->join('products as p', 'scsp.product_id', '=', 'p.id')
                ->where('scsp.business_id', $business_id)
                ->where('p.sub_category_id', $catId)
                ->whereDate('scsp.order_date', $date)
                ->sum(DB::raw('scsp.qty * scsp.price'));

            $row1 = (float)($today_sales->income ?? 0) - (float)($today_sales->credit ?? 0) - $petro_credit_today;
            $totalCash += max(0, $row1);
        }
        
        return $totalCash;
    }
}
