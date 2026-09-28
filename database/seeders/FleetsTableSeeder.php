<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FleetsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('fleets')->delete();
        
        
        
    }
}