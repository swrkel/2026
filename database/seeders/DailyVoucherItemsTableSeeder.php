<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DailyVoucherItemsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('daily_voucher_items')->delete();
        
        
        
    }
}