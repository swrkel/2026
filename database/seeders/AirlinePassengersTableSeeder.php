<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AirlinePassengersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('airline_passengers')->delete();
        
        
        
    }
}