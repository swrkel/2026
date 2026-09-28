<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EssentialsLeaveTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('essentials_leave_types')->delete();
        
        
        
    }
}