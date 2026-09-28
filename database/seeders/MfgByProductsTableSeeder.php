<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MfgByProductsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('mfg_by_products')->delete();
        
        
        
    }
}