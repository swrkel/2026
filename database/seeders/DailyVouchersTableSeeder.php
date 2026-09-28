<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DailyVouchersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('daily_vouchers')->delete();
        
        
        
    }
}