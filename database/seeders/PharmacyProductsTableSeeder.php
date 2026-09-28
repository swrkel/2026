<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PharmacyProductsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('pharmacy_products')->delete();
        
        
        
    }
}