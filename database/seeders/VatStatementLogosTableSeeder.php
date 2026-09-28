<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatStatementLogosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vat_statement_logos')->delete();
        
        \DB::table('vat_statement_logos')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'created_by' => 7,
                'logo' => 'public/img/fleet_logos/4/1738131073.jpg',
                'alignment' => 'Center',
                'image_name' => '1',
                'business_name' => 1,
                'business_address' => 1,
                'contact_no' => 1,
                'email' => 1,
                'mobile_no' => 1,
                'created_at' => '2024-03-23 07:58:14',
                'updated_at' => '2025-01-30 04:31:53',
                'statement_note' => NULL,
                'text_position' => 'below',
            ),
        ));
        
        
    }
}