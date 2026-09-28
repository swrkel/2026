<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoanTransactionProcessingStrategiesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('loan_transaction_processing_strategies')->delete();
        
        \DB::table('loan_transaction_processing_strategies')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Penalties, Fees, Interest, Principal order',
                'translated_name' => 'Penalties, Fees, Interest, Principal order',
                'active' => 1,
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Principal, Interest, Penalties, Fees Order',
                'translated_name' => 'Principal, Interest, Penalties, Fees Order',
                'active' => 1,
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Interest, Principal, Penalties, Fees Order',
                'translated_name' => 'Interest, Principal, Penalties, Fees Order',
                'active' => 1,
            ),
        ));
        
        
    }
}