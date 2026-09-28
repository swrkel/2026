<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatInvoicePaymentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vat_invoice_payments')->delete();
        
        
        
    }
}