<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ModulePermissionLocationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('module_permission_locations')->delete();
        
        \DB::table('module_permission_locations')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'module_name' => 'number_of_pumps',
                'locations' => '{"2":"13"}',
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:39:39',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'module_name' => 'accounting_module',
                'locations' => '{"2":"1"}',
                'created_at' => '2024-02-26 15:39:39',
                'updated_at' => '2024-02-26 15:39:39',
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 4,
                'module_name' => 'mf_module',
                'locations' => '{"2":"1"}',
                'created_at' => '2025-05-28 11:46:48',
                'updated_at' => '2025-05-28 11:46:48',
            ),
            3 => 
            array (
                'id' => 4,
                'business_id' => 4,
                'module_name' => 'restaurant_module',
                'locations' => '{"2":"1"}',
                'created_at' => '2025-05-28 11:46:48',
                'updated_at' => '2025-05-28 11:46:48',
            ),
        ));
        
        
    }
}