<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BlockCloseReasonsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('block_close_reasons')->delete();
        
        
        
    }
}