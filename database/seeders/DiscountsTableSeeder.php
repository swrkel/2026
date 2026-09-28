<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DiscountsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('discounts')->delete();
        
        
        
    }
}