<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatSettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vat_settings')->delete();
        
        \DB::table('vat_settings')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'vat_period' => 'quarterly',
                'effective_date' => '2024-03-01',
                'status' => 0,
                'created_by' => 7,
                'created_at' => '2024-03-05 05:15:23',
                'updated_at' => '2024-03-29 06:07:44',
                'is_custom_date' => 0,
                'tax_report_name' => 'vat',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'vat_period' => 'monthly',
                'effective_date' => '2024-03-01',
                'status' => 1,
                'created_by' => 7,
                'created_at' => '2024-03-29 06:07:44',
                'updated_at' => '2024-03-29 06:07:44',
                'is_custom_date' => 0,
                'tax_report_name' => 'tax',
            ),
        ));
        
        
    }
}