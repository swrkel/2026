<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatCreditBillsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vat_credit_bills')->delete();
        
        
        
    }
}