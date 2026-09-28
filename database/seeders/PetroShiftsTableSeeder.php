<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PetroShiftsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('petro_shifts')->delete();
        
        \DB::table('petro_shifts')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'pump_operator_id' => 2,
                'status' => 0,
                'created_at' => '2025-05-12 09:47:44',
                'updated_at' => '2025-05-12 09:47:44',
                'shift_date' => '2025-05-11 19:30:00',
                'closed_time' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'pump_operator_id' => 1,
                'status' => 0,
                'created_at' => '2025-05-12 09:48:09',
                'updated_at' => '2025-05-12 09:48:09',
                'shift_date' => '2025-05-11 19:30:00',
                'closed_time' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 4,
                'pump_operator_id' => 3,
                'status' => 0,
                'created_at' => '2025-05-14 09:38:58',
                'updated_at' => '2025-05-14 09:38:58',
                'shift_date' => '2025-05-13 19:30:00',
                'closed_time' => NULL,
            ),
            3 => 
            array (
                'id' => 4,
                'business_id' => 4,
                'pump_operator_id' => 4,
                'status' => 0,
                'created_at' => '2025-05-14 09:39:20',
                'updated_at' => '2025-05-14 09:39:20',
                'shift_date' => '2025-05-13 19:30:00',
                'closed_time' => NULL,
            ),
        ));
        
        
    }
}