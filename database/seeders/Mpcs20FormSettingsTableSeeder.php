<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class Mpcs20FormSettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('mpcs_20_form_settings')->delete();
        
        \DB::table('mpcs_20_form_settings')->insert(array (
            0 => 
            array (
                'id' => 7,
                'business_id' => '4',
                'opening_date' => '2025-04-01',
                'starting_number' => '00',
                'total_sale' => '100',
                'cash_sale' => '200',
                'credit_sale' => '300',
                'category' => '4,5,6,7,8,9,14,15',
                'created_by' => 7,
                'info' => NULL,
                'updated_at' => '2025-04-14 13:38:42',
                'created_at' => '2025-04-14 13:38:42',
            ),
        ));
        
        
    }
}