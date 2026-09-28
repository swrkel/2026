<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DailyCollectionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('daily_collections')->delete();
        
        \DB::table('daily_collections')->insert(array (
            0 => 
            array (
                'id' => 1,
                'shift_number' => NULL,
                'daily_shift' => 1,
                'business_id' => 4,
                'collection_form_no' => '1',
                'pump_operator_id' => 3,
                'settlement_id' => NULL,
                'settlement_date' => NULL,
                'balance_collection' => '0.00',
                'current_amount' => '1222.00',
                'location_id' => 2,
                'created_by' => 7,
                'created_at' => '2025-05-14 09:38:05',
                'updated_at' => '2025-05-14 09:38:05',
                'shift_id' => NULL,
                'bring_forward' => 0,
                'customer_bill_id' => NULL,
                'denom_qty' => NULL,
                'added_to_account' => NULL,
                'shift_no' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'shift_number' => NULL,
                'daily_shift' => 2,
                'business_id' => 4,
                'collection_form_no' => '2',
                'pump_operator_id' => 4,
                'settlement_id' => NULL,
                'settlement_date' => NULL,
                'balance_collection' => '0.00',
                'current_amount' => '334.00',
                'location_id' => 2,
                'created_by' => 7,
                'created_at' => '2025-05-14 09:39:20',
                'updated_at' => '2025-05-14 09:39:20',
                'shift_id' => NULL,
                'bring_forward' => 0,
                'customer_bill_id' => NULL,
                'denom_qty' => NULL,
                'added_to_account' => NULL,
                'shift_no' => NULL,
            ),
        ));
        
        
    }
}