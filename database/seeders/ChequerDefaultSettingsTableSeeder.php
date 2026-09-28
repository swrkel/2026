<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ChequerDefaultSettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('chequer_default_settings')->delete();
        
        
        
    }
}