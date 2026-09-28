<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FuelTanksTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('fuel_tanks')->delete();
        
        \DB::table('fuel_tanks')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'product_id' => 1,
                'fuel_tank_number' => 'Tank-P92',
                'fuel_type' => '',
                'location_id' => 2,
                'storage_volume' => '24100',
                'current_balance' => '56528911.85768996',
                'bulk_tank' => 0,
                'tank_manufacturer' => NULL,
                'tank_manufacturer_phone' => NULL,
                'tank_capacity' => NULL,
                'unit_name' => NULL,
                'user_id' => 7,
                'transaction_date' => '2024-02-25',
                'created_at' => '2024-02-27 03:13:39',
                'updated_at' => '2025-05-13 09:04:56',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'product_id' => 4,
                'fuel_tank_number' => 'Tank-LAD',
                'fuel_type' => '',
                'location_id' => 2,
                'storage_volume' => '24100',
                'current_balance' => '5404735.78196',
                'bulk_tank' => 0,
                'tank_manufacturer' => NULL,
                'tank_manufacturer_phone' => NULL,
                'tank_capacity' => NULL,
                'unit_name' => NULL,
                'user_id' => 7,
                'transaction_date' => '2024-02-25',
                'created_at' => '2024-02-27 03:14:04',
                'updated_at' => '2025-05-07 07:32:00',
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 4,
                'product_id' => 5,
                'fuel_tank_number' => 'Tank-LSD',
                'fuel_type' => '',
                'location_id' => 2,
                'storage_volume' => '16218',
                'current_balance' => '187281.8755',
                'bulk_tank' => 0,
                'tank_manufacturer' => NULL,
                'tank_manufacturer_phone' => NULL,
                'tank_capacity' => NULL,
                'unit_name' => NULL,
                'user_id' => 7,
                'transaction_date' => '2024-02-25',
                'created_at' => '2024-02-27 03:14:30',
                'updated_at' => '2025-05-07 08:19:03',
            ),
            3 => 
            array (
                'id' => 4,
                'business_id' => 4,
                'product_id' => 2,
                'fuel_tank_number' => 'Tank-XP95',
                'fuel_type' => '',
                'location_id' => 2,
                'storage_volume' => '16218',
                'current_balance' => '238577112.64000022',
                'bulk_tank' => 0,
                'tank_manufacturer' => NULL,
                'tank_manufacturer_phone' => NULL,
                'tank_capacity' => NULL,
                'unit_name' => NULL,
                'user_id' => 7,
                'transaction_date' => '2024-02-25',
                'created_at' => '2024-02-27 03:15:07',
                'updated_at' => '2025-05-07 08:02:34',
            ),
            4 => 
            array (
                'id' => 5,
                'business_id' => 4,
                'product_id' => 6,
                'fuel_tank_number' => 'Tank-XM',
                'fuel_type' => '',
                'location_id' => 2,
                'storage_volume' => '9561',
                'current_balance' => '9306.84',
                'bulk_tank' => 0,
                'tank_manufacturer' => NULL,
                'tank_manufacturer_phone' => NULL,
                'tank_capacity' => NULL,
                'unit_name' => NULL,
                'user_id' => 7,
                'transaction_date' => '2024-02-25',
                'created_at' => '2024-02-27 03:15:44',
                'updated_at' => '2025-04-09 08:55:04',
            ),
            5 => 
            array (
                'id' => 6,
                'business_id' => 4,
                'product_id' => 3,
                'fuel_tank_number' => 'Tank-XPE3',
                'fuel_type' => '',
                'location_id' => 2,
                'storage_volume' => '9561',
                'current_balance' => '19926',
                'bulk_tank' => 0,
                'tank_manufacturer' => NULL,
                'tank_manufacturer_phone' => NULL,
                'tank_capacity' => NULL,
                'unit_name' => NULL,
                'user_id' => 7,
                'transaction_date' => '2024-02-25',
                'created_at' => '2024-02-27 03:16:22',
                'updated_at' => '2025-04-09 08:24:09',
            ),
        ));
        
        
    }
}