<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class StockAdjustmentLinesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('stock_adjustment_lines')->delete();
        
        \DB::table('stock_adjustment_lines')->insert(array (
            0 => 
            array (
                'id' => 1,
                'transaction_id' => 993,
                'product_id' => 196,
                'variation_id' => 196,
                'quantity' => '1.0000',
                'unit_price' => '79.6500',
                'type' => 'normal',
                'stock_adjustment_type' => 'decrease',
                'removed_purchase_line' => NULL,
                'lot_no_line_id' => NULL,
                'tank_id' => NULL,
                'inventory_adjustment_account' => NULL,
                'created_at' => '2024-03-14 07:24:06',
                'updated_at' => '2024-03-14 07:24:06',
            ),
        ));
        
        
    }
}