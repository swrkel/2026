<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatExpenseCategoriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vat_expense_categories')->delete();
        
        
        
    }
}