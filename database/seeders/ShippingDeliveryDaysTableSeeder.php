<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ShippingDeliveryDaysTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('shipping_delivery_days')->delete();
        
        
        
    }
}