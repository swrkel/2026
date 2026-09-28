<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ChequeNumberMaintainsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('cheque_number_maintains')->delete();
        
        
        
    }
}