<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DailyCardsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('daily_cards')->delete();
        
        
        
    }
}