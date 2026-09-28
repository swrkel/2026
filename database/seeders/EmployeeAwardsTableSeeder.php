<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EmployeeAwardsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('employee_awards')->delete();
        
        
        
    }
}