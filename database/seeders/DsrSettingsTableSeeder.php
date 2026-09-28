<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DsrSettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('dsr_settings')->delete();
        
        \DB::table('dsr_settings')->insert(array (
            0 => 
            array (
                'id' => 1,
                'date_time' => '2023-12-07 02:02:47',
                'country_id' => 1,
                'province_id' => 1,
                'district_id' => 1,
                'areas' => '["1"]',
                'fuel_provider_id' => 1,
                'product_id' => NULL,
                'accumulative_sale' => NULL,
                'accumulative_purchase' => NULL,
                'dealer_number' => 'BES43',
                'dealer_name' => 'kemboi kosgei',
                'dsr_starting_number' => 0,
                'user_id' => 1,
                'business_id' => 1,
                'created_at' => '2023-12-06 22:17:12',
                'updated_at' => '2023-12-06 22:17:12',
            ),
            1 => 
            array (
                'id' => 2,
                'date_time' => '2023-12-07 02:02:47',
                'country_id' => 2,
                'province_id' => 4,
                'district_id' => 3,
                'areas' => '["2"]',
                'fuel_provider_id' => 2,
                'product_id' => NULL,
                'accumulative_sale' => NULL,
                'accumulative_purchase' => NULL,
                'dealer_number' => 'D No 12',
                'dealer_name' => 'Dealer 1',
                'dsr_starting_number' => 123,
                'user_id' => 1,
                'business_id' => 1,
                'created_at' => '2023-12-07 08:02:40',
                'updated_at' => '2023-12-07 08:02:40',
            ),
            2 => 
            array (
                'id' => 3,
                'date_time' => '2023-12-07 02:02:47',
                'country_id' => 0,
                'province_id' => 0,
                'district_id' => 0,
                'areas' => '',
                'fuel_provider_id' => 0,
                'product_id' => 1,
                'accumulative_sale' => '1222',
                'accumulative_purchase' => '2222',
                'dealer_number' => '',
                'dealer_name' => '',
                'dsr_starting_number' => 0,
                'user_id' => 1,
                'business_id' => 1,
                'created_at' => '2023-12-10 11:30:30',
                'updated_at' => '2023-12-10 11:30:30',
            ),
            3 => 
            array (
                'id' => 4,
                'date_time' => '2023-12-07 02:02:47',
                'country_id' => 0,
                'province_id' => 0,
                'district_id' => 0,
                'areas' => '',
                'fuel_provider_id' => 0,
                'product_id' => 2,
                'accumulative_sale' => '23',
                'accumulative_purchase' => '32',
                'dealer_number' => '',
                'dealer_name' => '',
                'dsr_starting_number' => 0,
                'user_id' => 1,
                'business_id' => 1,
                'created_at' => '2023-12-10 12:34:04',
                'updated_at' => '2023-12-10 12:34:04',
            ),
        ));
        
        
    }
}