<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PetroDailyShiftsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('petro_daily_shifts')->delete();
        
        \DB::table('petro_daily_shifts')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'pump_operator_pending' => '1,2,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23',
                'pump_operator_assigned' => '3',
                'shift_no' => 'DSN-001',
                'date' => '2025-05-28 00:00:00',
                'time' => '07:26:00',
                'user' => 7,
                'status' => 0,
                'updated_at' => '2025-05-28 07:36:23',
                'created_at' => '2025-05-23 12:11:09',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'pump_operator_pending' => '',
                'pump_operator_assigned' => '',
                'shift_no' => 'DSN-002',
                'date' => '2025-05-23 00:00:00',
                'time' => '12:11:33',
                'user' => 7,
                'status' => 1,
                'updated_at' => '2025-05-23 12:11:33',
                'created_at' => '2025-05-23 12:11:33',
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 4,
                'pump_operator_pending' => '',
                'pump_operator_assigned' => '',
                'shift_no' => 'DSN-003',
                'date' => '2025-05-28 00:00:00',
                'time' => '07:36:23',
                'user' => 7,
                'status' => 1,
                'updated_at' => '2025-05-28 07:36:23',
                'created_at' => '2025-05-28 07:36:23',
            ),
        ));
        
        
    }
}