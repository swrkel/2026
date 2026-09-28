<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EmploymentStatusesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('employment_statuses')->delete();
        
        
        
    }
}