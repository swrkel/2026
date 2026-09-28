<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CustomerBillVatPrefixesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('customer_bill_vat_prefixes')->delete();
        
        
        
    }
}