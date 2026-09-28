<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class JournalsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('journals')->delete();
        
        
        
    }
}