<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RepairJobSheetsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('repair_job_sheets')->delete();
        
        \DB::table('repair_job_sheets')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'location_id' => 2,
                'contact_id' => 6,
                'job_sheet_no' => 'PX2025/0001',
                'service_type' => 'carry_in',
                'pick_up_on_site_addr' => NULL,
                'brand_id' => 8,
                'device_id' => 519,
                'device_model_id' => 1,
                'checklist' => NULL,
                'security_pwd' => '1',
                'security_pattern' => NULL,
                'serial_no' => '1',
                'status_id' => 1,
                'delivery_date' => '2025-05-28 16:35:00',
                'product_configuration' => NULL,
                'defects' => NULL,
                'product_condition' => NULL,
                'service_staff' => NULL,
                'comment_by_ss' => NULL,
                'estimated_cost' => NULL,
                'created_by' => 7,
                'created_at' => '2025-05-28 12:18:33',
                'updated_at' => '2025-05-28 12:18:33',
                'custom_field_1' => NULL,
                'custom_field_2' => NULL,
                'custom_field_3' => NULL,
                'custom_field_4' => NULL,
                'custom_field_5' => NULL,
                'parts' => NULL,
                'warranty_number' => NULL,
                'reportStatus' => 1,
                'vehicle_id' => 148,
            ),
        ));
        
        
    }
}