<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SettlementExpensePaymentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('settlement_expense_payments')->delete();
        
        \DB::table('settlement_expense_payments')->insert(array (
            0 => 
            array (
                'id' => 1,
                'settlement_no' => '1791',
                'business_id' => 4,
                'expense_number' => 'EP-1-1',
                'category_id' => 65,
                'reference_no' => 'EP2024/0281',
                'account_id' => 177,
                'reason' => NULL,
                'amount' => '480.000000',
                'transaction_id' => 12001,
                'created_at' => '2024-09-12 10:48:24',
                'updated_at' => '2024-09-12 10:48:32',
            ),
        ));
        
        
    }
}