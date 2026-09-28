<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class StoresTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('stores')->delete();
        
        \DB::table('stores')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 2,
                'location_id' => 2,
                'name' => 'Main Store',
                'address' => '0',
                'contact_number' => 0,
                'stock' => 0.0,
                'status' => 1,
                'is_main' => 1,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 3,
                'location_id' => 3,
                'name' => 'Main Store',
                'address' => '0',
                'contact_number' => 0,
                'stock' => 0.0,
                'status' => 1,
                'is_main' => 1,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 4,
                'location_id' => 2,
                'name' => 'Main Store',
                'address' => '0',
                'contact_number' => 0,
                'stock' => 0.0,
                'status' => 1,
                'is_main' => 1,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
        ));
        
        
    }
}