<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoanTransactionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('loan_transactions')->delete();
        
        \DB::table('loan_transactions')->insert(array (
            0 => 
            array (
                'id' => 1,
                'loan_id' => 2,
                'created_by_id' => 3,
                'location_id' => 3,
                'payment_detail_id' => NULL,
                'name' => 'Disbursement',
                'amount' => '100000.000000',
                'credit' => NULL,
                'debit' => '100000.000000',
                'principal_repaid_derived' => '0.000000',
                'interest_repaid_derived' => '0.000000',
                'fees_repaid_derived' => '0.000000',
                'penalties_repaid_derived' => '0.000000',
                'loan_transaction_type_id' => 1,
                'reversed' => 0,
                'reversible' => 0,
                'submitted_on' => '2023-06-03',
                'due_date' => NULL,
                'created_on' => '2023-06-06',
                'status' => NULL,
                'reference' => NULL,
                'gateway_id' => NULL,
                'description' => NULL,
                'payment_gateway_data' => NULL,
                'online_transaction' => 0,
                'created_at' => '2023-06-06 02:35:53',
                'updated_at' => '2023-06-06 02:35:53',
            ),
            1 => 
            array (
                'id' => 2,
                'loan_id' => 2,
                'created_by_id' => 3,
                'location_id' => 3,
                'payment_detail_id' => NULL,
                'name' => 'Interest Applied',
                'amount' => '9000.000000',
                'credit' => NULL,
                'debit' => '9000.000000',
                'principal_repaid_derived' => '0.000000',
                'interest_repaid_derived' => '0.000000',
                'fees_repaid_derived' => '0.000000',
                'penalties_repaid_derived' => '0.000000',
                'loan_transaction_type_id' => 11,
                'reversed' => 0,
                'reversible' => 0,
                'submitted_on' => '2023-06-03',
                'due_date' => NULL,
                'created_on' => '2023-06-06',
                'status' => NULL,
                'reference' => NULL,
                'gateway_id' => NULL,
                'description' => NULL,
                'payment_gateway_data' => NULL,
                'online_transaction' => 0,
                'created_at' => '2023-06-06 02:35:53',
                'updated_at' => '2023-06-06 02:35:53',
            ),
        ));
        
        
    }
}