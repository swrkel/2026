<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BoatTripsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('boat_trips')->delete();
        
        
        
    }
}