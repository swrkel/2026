<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatInvoice2PrefixesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vat_invoice2_prefixes')->delete();
        
        \DB::table('vat_invoice2_prefixes')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'prefix' => 'InvoiceNo',
                'starting_no' => 45,
                'created_by' => 7,
                'created_at' => '2024-10-21 08:24:19',
                'updated_at' => '2024-03-29 05:58:33',
            ),
        ));
        
        
    }
}