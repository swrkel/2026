<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ShippingPrefixTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('shipping_prefix')->delete();
        
        
        
    }
}