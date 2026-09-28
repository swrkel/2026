<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BakeryInvoiceNumbersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('bakery_invoice_numbers')->delete();
        
        
        
    }
}