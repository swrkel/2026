<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SettlementChequePaymentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('settlement_cheque_payments')->delete();
        
        \DB::table('settlement_cheque_payments')->insert(array (
            0 => 
            array (
                'id' => 1,
                'settlement_no' => '3896',
                'business_id' => 4,
                'customer_id' => 6,
                'bank_name' => 'Bank 1',
                'cheque_number' => '4589',
                'cheque_date' => '2025-05-05',
                'amount' => '16146.000000',
                'customer_payment_id' => 18417,
                'note' => NULL,
                'created_at' => '2025-05-05 08:19:30',
                'updated_at' => '2025-05-05 08:19:33',
                'post_dated_cheque' => NULL,
            ),
        ));
        
        
    }
}