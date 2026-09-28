<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatExpensesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vat_expenses')->delete();
        
        
        
    }
}