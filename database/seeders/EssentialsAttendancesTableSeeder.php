<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EssentialsAttendancesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('essentials_attendances')->delete();
        
        
        
    }
}