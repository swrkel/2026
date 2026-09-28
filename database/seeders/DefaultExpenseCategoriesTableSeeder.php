<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DefaultExpenseCategoriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('default_expense_categories')->delete();
        
        
        
    }
}