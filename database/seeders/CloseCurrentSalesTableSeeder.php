<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CloseCurrentSalesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('close_current_sales')->delete();
        
        
        
    }
}