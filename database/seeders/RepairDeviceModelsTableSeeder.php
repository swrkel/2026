<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RepairDeviceModelsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('repair_device_models')->delete();
        
        \DB::table('repair_device_models')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'name' => 'Honda',
                'repair_checklist' => NULL,
                'brand_id' => 8,
                'device_id' => 519,
                'created_by' => 7,
                'created_at' => '2025-05-28 11:50:14',
                'updated_at' => '2025-05-28 11:50:14',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'name' => 'Toyota',
                'repair_checklist' => NULL,
                'brand_id' => 8,
                'device_id' => 520,
                'created_by' => 7,
                'created_at' => '2025-05-28 11:50:32',
                'updated_at' => '2025-05-28 11:50:32',
            ),
        ));
        
        
    }
}