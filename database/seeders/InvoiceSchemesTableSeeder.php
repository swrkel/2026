<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InvoiceSchemesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('invoice_schemes')->delete();
        
        \DB::table('invoice_schemes')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 1,
                'name' => 'Default',
                'scheme_type' => 'blank',
                'prefix' => '',
                'start_number' => 1,
                'invoice_count' => 107,
                'total_digits' => 4,
                'is_default' => 1,
                'created_at' => '2019-12-30 12:22:03',
                'updated_at' => '2020-05-30 13:08:04',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 2,
                'name' => 'Default',
                'scheme_type' => 'blank',
                'prefix' => '',
                'start_number' => 1,
                'invoice_count' => 0,
                'total_digits' => 4,
                'is_default' => 1,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 3,
                'name' => 'Default',
                'scheme_type' => 'blank',
                'prefix' => '',
                'start_number' => 1,
                'invoice_count' => 0,
                'total_digits' => 4,
                'is_default' => 1,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
            ),
            3 => 
            array (
                'id' => 4,
                'business_id' => 4,
                'name' => 'Default',
                'scheme_type' => 'blank',
                'prefix' => NULL,
                'start_number' => 125,
                'invoice_count' => 124,
                'total_digits' => 4,
                'is_default' => 1,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-12-17 08:13:43',
            ),
        ));
        
        
    }
}