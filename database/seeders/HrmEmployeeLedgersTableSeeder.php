<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HrmEmployeeLedgersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('hrm_employee_ledgers')->delete();
        
        
        
    }
}