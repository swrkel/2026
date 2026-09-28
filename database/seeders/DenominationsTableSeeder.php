<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DenominationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('denominations')->delete();
        
        
        
    }
}