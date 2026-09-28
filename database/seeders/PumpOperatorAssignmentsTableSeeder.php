<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PumpOperatorAssignmentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('pump_operator_assignments')->delete();
        
        \DB::table('pump_operator_assignments')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'pump_id' => 1,
                'pump_operator_id' => 2,
                'starting_meter' => '1372200.880000',
                'closing_meter' => '0.000000',
                'date_and_time' => '2025-05-12 09:47:00',
                'close_date_and_time' => NULL,
                'status' => 'open',
                'settlement_id' => NULL,
                'created_at' => '2025-05-12 09:47:44',
                'updated_at' => '2025-05-12 09:47:44',
                'assigned_by' => 7,
                'is_confirmed' => 0,
                'confirmed_at' => NULL,
                'is_manually_closed' => 0,
                'pump_operator_other_sale_id' => NULL,
                'closed_in_settlement' => 0,
                'shift_id' => 1,
                'shift_number' => 1,
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'pump_id' => 3,
                'pump_operator_id' => 1,
                'starting_meter' => '6315400.200000',
                'closing_meter' => '0.000000',
                'date_and_time' => '2025-05-12 09:48:00',
                'close_date_and_time' => NULL,
                'status' => 'open',
                'settlement_id' => NULL,
                'created_at' => '2025-05-12 09:48:09',
                'updated_at' => '2025-05-12 09:48:09',
                'assigned_by' => 7,
                'is_confirmed' => 0,
                'confirmed_at' => NULL,
                'is_manually_closed' => 0,
                'pump_operator_other_sale_id' => NULL,
                'closed_in_settlement' => 0,
                'shift_id' => 2,
                'shift_number' => 2,
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 4,
                'pump_id' => 4,
                'pump_operator_id' => 4,
                'starting_meter' => '4082766.020000',
                'closing_meter' => '0.000000',
                'date_and_time' => '2025-05-14 09:38:00',
                'close_date_and_time' => NULL,
                'status' => 'open',
                'settlement_id' => NULL,
                'created_at' => '2025-05-14 09:38:58',
                'updated_at' => '2025-05-14 09:39:20',
                'assigned_by' => 7,
                'is_confirmed' => 0,
                'confirmed_at' => NULL,
                'is_manually_closed' => 0,
                'pump_operator_other_sale_id' => NULL,
                'closed_in_settlement' => 0,
                'shift_id' => 3,
                'shift_number' => 3,
            ),
        ));
        
        
    }
}