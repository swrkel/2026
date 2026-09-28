<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UnloadStocksTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('unload_stocks')->delete();
        
        
        
    }
}