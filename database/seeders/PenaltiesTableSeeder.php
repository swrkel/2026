<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PenaltiesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('penalties')->delete();
        
        
        
    }
}