<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatPurchaseProductsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vat_purchase_products')->delete();
        
        
        
    }
}