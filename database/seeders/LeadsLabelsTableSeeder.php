<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LeadsLabelsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('leads_labels')->delete();
        
        
        
    }
}