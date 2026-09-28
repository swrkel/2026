<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoanProductsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('loan_products')->delete();
        
        
        
    }
}