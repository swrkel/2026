<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DipResettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('dip_resettings')->delete();
        
        
        
    }
}