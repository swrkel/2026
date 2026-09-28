<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HmsTransactionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('hms_transactions')->delete();
        
        
        
    }
}