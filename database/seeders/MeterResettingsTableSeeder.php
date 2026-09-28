<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MeterResettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('meter_resettings')->delete();
        
        
        
    }
}