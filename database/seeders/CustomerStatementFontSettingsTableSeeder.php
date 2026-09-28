<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CustomerStatementFontSettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('customer_statement_font_settings')->delete();
        
        \DB::table('customer_statement_font_settings')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'settings' => '{"header_size":"20","company_size":"15","address_size":"15","left_header_size":"15","right_header_size":"15","thead_size":"15","tbody_size":"15","sub_size":"15","system_footer_size":"12","footer_size":"15"}',
                'created_at' => '2024-10-04 12:59:33',
                'updated_at' => '2024-10-04 12:59:33',
            ),
        ));
        
        
    }
}