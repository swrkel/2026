<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ShippingRecipientsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('shipping_recipients')->delete();
        
        
        
    }
}