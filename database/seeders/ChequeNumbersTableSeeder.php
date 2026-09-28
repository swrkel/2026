<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ChequeNumbersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('cheque_numbers')->delete();
        
        
        
    }
}