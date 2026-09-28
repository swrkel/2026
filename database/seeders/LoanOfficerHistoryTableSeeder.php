<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoanOfficerHistoryTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('loan_officer_history')->delete();
        
        \DB::table('loan_officer_history')->insert(array (
            0 => 
            array (
                'id' => 1,
                'loan_id' => 1,
                'created_by_id' => 3,
                'loan_officer_id' => 5,
                'start_date' => '2023-06-02',
                'end_date' => NULL,
                'created_at' => '2023-06-02 15:32:24',
                'updated_at' => '2023-06-02 15:32:24',
            ),
            1 => 
            array (
                'id' => 2,
                'loan_id' => 2,
                'created_by_id' => 3,
                'loan_officer_id' => 3,
                'start_date' => '2023-06-03',
                'end_date' => NULL,
                'created_at' => '2023-06-03 08:25:55',
                'updated_at' => '2023-06-03 08:25:55',
            ),
        ));
        
        
    }
}