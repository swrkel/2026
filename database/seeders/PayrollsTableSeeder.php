<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PayrollsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('payrolls')->delete();
        
        
        
    }
}