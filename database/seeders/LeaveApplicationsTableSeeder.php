<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LeaveApplicationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('leave_applications')->delete();
        
        
        
    }
}