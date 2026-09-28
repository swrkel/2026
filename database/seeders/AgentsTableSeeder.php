<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AgentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('agents')->delete();
        
        
        
    }
}