<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoanHistoryTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('loan_history')->delete();
        
        \DB::table('loan_history')->insert(array (
            0 => 
            array (
                'id' => 1,
                'loan_id' => 1,
                'created_by_id' => 3,
                'action' => 'Loan Created',
                'user' => '29copy 1',
                'created_at' => '2023-06-02 15:32:24',
                'updated_at' => '2023-06-02 15:32:24',
            ),
            1 => 
            array (
                'id' => 2,
                'loan_id' => 2,
                'created_by_id' => 3,
                'action' => 'Loan Created',
                'user' => '29copy 1',
                'created_at' => '2023-06-03 08:25:54',
                'updated_at' => '2023-06-03 08:25:54',
            ),
            2 => 
            array (
                'id' => 3,
                'loan_id' => 2,
                'created_by_id' => 3,
                'action' => 'Loan Approved',
                'user' => '29copy 1',
                'created_at' => '2023-06-03 08:47:58',
                'updated_at' => '2023-06-03 08:47:58',
            ),
            3 => 
            array (
                'id' => 4,
                'loan_id' => 2,
                'created_by_id' => 3,
                'action' => 'Loan Disbursed',
                'user' => '29copy 1',
                'created_at' => '2023-06-06 02:34:37',
                'updated_at' => '2023-06-06 02:34:37',
            ),
            4 => 
            array (
                'id' => 5,
                'loan_id' => 2,
                'created_by_id' => 3,
                'action' => 'Loan Disbursed',
                'user' => '29copy 1',
                'created_at' => '2023-06-06 02:35:53',
                'updated_at' => '2023-06-06 02:35:53',
            ),
        ));
        
        
    }
}