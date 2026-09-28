<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ShippingCitiesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('shipping_cities')->delete();
        
        
        
    }
}