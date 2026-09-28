<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TripCategoriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('trip_categories')->delete();
        
        
        
    }
}