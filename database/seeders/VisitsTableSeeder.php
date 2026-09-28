<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VisitsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('visits')->delete();
        
        
        
    }
}