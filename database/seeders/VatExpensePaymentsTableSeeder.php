<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatExpensePaymentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vat_expense_payments')->delete();
        
        
        
    }
}