<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoanTransactionTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('loan_transaction_types')->delete();
        
        \DB::table('loan_transaction_types')->insert(array (
            0 => 
            array (
                'id' => 14,
                'name' => 'Withdrawal',
                'translated_name' => 'Withdrawal',
                'active' => 1,
            ),
        ));
        
        
    }
}