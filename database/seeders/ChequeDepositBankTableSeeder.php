<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ChequeDepositBankTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('cheque_deposit_bank')->delete();
        
        \DB::table('cheque_deposit_bank')->insert(array (
            0 => 
            array (
                'id' => 3,
                'account_trans_id' => 60082,
                'bank_id' => 190,
                'cheque_number' => '60078',
                'created_at' => '2025-05-05 08:23:05',
                'updated_at' => '2025-05-05 08:23:05',
            ),
            1 => 
            array (
                'id' => 4,
                'account_trans_id' => 60122,
                'bank_id' => 188,
                'cheque_number' => '60119',
                'created_at' => '2025-05-22 13:19:50',
                'updated_at' => '2025-05-22 13:19:50',
            ),
        ));
        
        
    }
}