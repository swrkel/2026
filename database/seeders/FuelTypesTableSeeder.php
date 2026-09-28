<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FuelTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('fuel_types')->delete();
        
        
        
    }
}