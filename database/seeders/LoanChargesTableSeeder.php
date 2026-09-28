<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoanChargesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('loan_charges')->delete();
        
        \DB::table('loan_charges')->insert(array (
            0 => 
            array (
                'id' => 1,
                'created_by_id' => 3,
                'currency_id' => 0,
                'loan_charge_type_id' => 0,
                'loan_charge_option_id' => 0,
                'name' => 'Processing Fee',
                'amount' => '10.000000',
                'min_amount' => NULL,
                'max_amount' => NULL,
                'payment_mode' => 'regular',
                'schedule' => 0,
                'schedule_frequency' => NULL,
                'schedule_frequency_type' => NULL,
                'is_penalty' => 0,
                'active' => 1,
                'allow_override' => 0,
                'created_at' => '2023-05-26 20:23:21',
                'updated_at' => '2023-05-26 20:23:21',
            ),
        ));
        
        
    }
}