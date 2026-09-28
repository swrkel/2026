<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BakeryLoadingReturnProductsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('bakery_loading_return_products')->delete();
        
        
        
    }
}