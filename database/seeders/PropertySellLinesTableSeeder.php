<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PropertySellLinesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('property_sell_lines')->delete();
        
        
        
    }
}