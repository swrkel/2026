<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CustomerStatementLogosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('customer_statement_logos')->delete();
        
        \DB::table('customer_statement_logos')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'created_by' => 7,
                'logo' => 'public/img/fleet_logos/4/1738131232.jpg',
                'alignment' => 'Left',
                'image_name' => '1',
                'business_name' => 1,
                'business_address' => 1,
                'contact_no' => 1,
                'email' => 1,
                'mobile_no' => 1,
                'created_at' => '2024-03-12 05:40:49',
                'updated_at' => '2025-01-29 07:13:52',
                'statement_note' => NULL,
                'text_position' => 'above',
            ),
        ));
        
        
    }
}