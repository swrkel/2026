<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WastagesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('wastages')->delete();
        
        
        
    }
}