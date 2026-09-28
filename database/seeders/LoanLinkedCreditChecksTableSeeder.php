<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoanLinkedCreditChecksTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('loan_linked_credit_checks')->delete();
        
        
        
    }
}