<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CashRegistersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('cash_registers')->delete();
        
        \DB::table('cash_registers')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'location_id' => 2,
                'user_id' => 42,
                'status' => 'open',
                'closed_at' => NULL,
                'closing_amount' => '0.0000',
                'total_card_slips' => 0,
                'total_cheques' => 0,
                'total_credit_sale' => '0.0000',
                'closing_note' => NULL,
                'created_at' => '2024-03-05 06:36:47',
                'updated_at' => '2024-03-05 06:36:47',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'location_id' => 2,
                'user_id' => 7,
                'status' => 'open',
                'closed_at' => NULL,
                'closing_amount' => '0.0000',
                'total_card_slips' => 0,
                'total_cheques' => 0,
                'total_credit_sale' => '0.0000',
                'closing_note' => NULL,
                'created_at' => '2024-03-07 07:20:13',
                'updated_at' => '2024-03-07 07:20:13',
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 4,
                'location_id' => 2,
                'user_id' => 38,
                'status' => 'open',
                'closed_at' => NULL,
                'closing_amount' => '0.0000',
                'total_card_slips' => 0,
                'total_cheques' => 0,
                'total_credit_sale' => '0.0000',
                'closing_note' => NULL,
                'created_at' => '2024-07-17 11:04:59',
                'updated_at' => '2024-07-17 11:04:59',
            ),
        ));
        
        
    }
}