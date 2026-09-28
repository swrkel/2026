<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CustomerPurchasesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('customer_purchases')->delete();
        
        
        
    }
}