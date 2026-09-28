<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoanCollateralHistoryTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('loan_collateral_history')->delete();
        
        
        
    }
}