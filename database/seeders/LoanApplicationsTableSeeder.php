<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoanApplicationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('loan_applications')->delete();
        
        
        
    }
}