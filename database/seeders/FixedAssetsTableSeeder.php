<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FixedAssetsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('fixed_assets')->delete();
        
        
        
    }
}