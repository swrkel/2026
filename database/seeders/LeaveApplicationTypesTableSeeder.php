<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LeaveApplicationTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('leave_application_types')->delete();
        
        \DB::table('leave_application_types')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 2,
                'leave_type' => 'Half Day',
                'allowed_days' => NULL,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
                'is_default' => 0,
                'is_superadmin_default' => 0,
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 2,
                'leave_type' => 'Short Leave',
                'allowed_days' => NULL,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
                'is_default' => 0,
                'is_superadmin_default' => 0,
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 3,
                'leave_type' => 'Half Day',
                'allowed_days' => NULL,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
                'is_default' => 0,
                'is_superadmin_default' => 0,
            ),
            3 => 
            array (
                'id' => 4,
                'business_id' => 3,
                'leave_type' => 'Short Leave',
                'allowed_days' => NULL,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
                'is_default' => 0,
                'is_superadmin_default' => 0,
            ),
            4 => 
            array (
                'id' => 5,
                'business_id' => 4,
                'leave_type' => 'Half Day',
                'allowed_days' => NULL,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
                'is_default' => 0,
                'is_superadmin_default' => 0,
            ),
            5 => 
            array (
                'id' => 6,
                'business_id' => 4,
                'leave_type' => 'Short Leave',
                'allowed_days' => NULL,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
                'is_default' => 0,
                'is_superadmin_default' => 0,
            ),
        ));
        
        
    }
}