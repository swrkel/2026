<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatUserInvoicePrefixesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vat_user_invoice_prefixes')->delete();
        
        \DB::table('vat_user_invoice_prefixes')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'location_id' => 2,
                'prefix_id' => 2,
                'prefix_id2' => 1,
                'user_id' => 7,
                'created_by' => 7,
                'date_time' => '2024-03-05 05:14:00',
                'created_at' => '2024-03-28 04:31:52',
                'updated_at' => '2024-03-28 04:31:52',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'location_id' => 2,
                'prefix_id' => 2,
                'prefix_id2' => 1,
                'user_id' => 38,
                'created_by' => 7,
                'date_time' => '2024-03-01 05:19:00',
                'created_at' => '2024-03-28 04:32:10',
                'updated_at' => '2024-03-28 04:32:10',
            ),
            2 => 
            array (
                'id' => 4,
                'business_id' => 4,
                'location_id' => 2,
                'prefix_id' => 2,
                'prefix_id2' => 1,
                'user_id' => 58,
                'created_by' => 7,
                'date_time' => '2024-09-12 11:57:00',
                'created_at' => '2024-09-12 11:58:00',
                'updated_at' => '2024-09-12 11:58:00',
            ),
        ));
        
        
    }
}