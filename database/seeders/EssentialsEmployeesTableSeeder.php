<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EssentialsEmployeesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('essentials_employees')->delete();
        
        \DB::table('essentials_employees')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Employee 1',
                'employee_no' => 1,
                'date_joined' => '2025-04-30 19:30:00',
                'designation' => 1,
                'department' => 1,
                'salary' => '25000.000',
                'probation_ends' => '2025-10-31 19:30:00',
                'business_id' => 4,
                'created_by' => 7,
                'dob' => '2025-04-30 19:30:00',
                'nic' => '1',
                'address' => '1',
                'created_at' => '2025-05-03 03:00:46',
                'updated_at' => '2025-05-03 03:00:46',
                'sales_target_applicable' => 0,
                'employee_ob' => NULL,
                'transaction_id' => NULL,
                'note' => '1',
            ),
        ));
        
        
    }
}