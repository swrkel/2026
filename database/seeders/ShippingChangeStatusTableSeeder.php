<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ShippingChangeStatusTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('shipping_change_status')->delete();
        
        
        
    }
}