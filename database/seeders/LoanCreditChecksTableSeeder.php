<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoanCreditChecksTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('loan_credit_checks')->delete();
        
        
        
    }
}