<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class Mpcs16aFormSettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('mpcs_16a_form_settings')->delete();
        
        \DB::table('mpcs_16a_form_settings')->insert(array (
            0 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'date' => '2025-04-01',
                'time' => '09:58:00',
                'starting_number' => '13',
                'ref_pre_form_number' => NULL,
                'no_of_rows_per_page' => '10',
                'total_purchase_price_with_vat' => '5000.00',
                'total_sale_price_with_vat' => '8000.00',
                'created_by' => 7,
                'created_at' => '2025-04-28 05:47:09',
                'updated_at' => '2025-04-28 05:28:40',
            ),
        ));
        
        
    }
}