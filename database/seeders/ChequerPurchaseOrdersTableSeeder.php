<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ChequerPurchaseOrdersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('chequer_purchase_orders')->delete();
        
        
        
    }
}