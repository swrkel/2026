<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BaseChangeLogTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('base_change_log')->delete();
        
        
        
    }
}