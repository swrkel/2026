<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatInvoice2SettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vat_invoice2_settings')->delete();
        
        \DB::table('vat_invoice2_settings')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'settings' => '{"header_size":"20","company_size":"20","address_size":"18","customer_size":"18","vat_size":"18","method_size":"18","registration_size":"20","invoice_size":"18","date_size":"18","thead_size":"18","tbody_size":"18","sub_size":"18","footer_size":"18"}',
                'created_at' => '2024-04-05 09:40:19',
                'updated_at' => '2024-04-05 09:40:19',
            ),
        ));
        
        
    }
}