<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class StockAdjustmentsTempTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('stock_adjustments_temp')->delete();
        
        
        
    }
}