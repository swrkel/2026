<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DailyChequePaymentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('daily_cheque_payments')->delete();
        
        
        
    }
}