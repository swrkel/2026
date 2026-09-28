<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatPrefixesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vat_prefixes')->delete();
        
        \DB::table('vat_prefixes')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'prefix' => 'PB/CS',
                'starting_no' => 11,
                'created_by' => 7,
                'created_at' => '2024-03-20 16:52:27',
                'updated_at' => '2024-03-20 16:52:27',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'prefix' => 'InvoiceNo',
                'starting_no' => 43,
                'created_by' => 7,
                'created_at' => '2024-10-21 08:25:37',
                'updated_at' => '2024-03-27 07:17:06',
            ),
        ));
        
        
    }
}