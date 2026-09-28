<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoanApprovalOfficersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('loan_approval_officers')->delete();
        
        \DB::table('loan_approval_officers')->insert(array (
            0 => 
            array (
                'id' => 1,
                'product_id' => 1,
                'loan_id' => 1,
                'user_id' => 4,
                'status' => 'pending',
            ),
            1 => 
            array (
                'id' => 2,
                'product_id' => 6,
                'loan_id' => 2,
                'user_id' => 3,
                'status' => 'approved',
            ),
        ));
        
        
    }
}