<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RepairStatusesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('repair_statuses')->delete();
        
        \DB::table('repair_statuses')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Pending',
                'color' => '#ea1b51',
                'sort_order' => 1,
                'business_id' => 4,
                'created_at' => '2023-01-09 11:36:20',
                'updated_at' => '2023-01-09 11:36:20',
                'email_body' => NULL,
                'email_subject' => NULL,
                'is_completed_status' => 0,
                'sms_template' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'In progress',
                'color' => '#1288ec',
                'sort_order' => 2,
                'business_id' => 4,
                'created_at' => '2023-01-09 11:36:47',
                'updated_at' => '2023-01-09 11:36:47',
                'email_body' => NULL,
                'email_subject' => NULL,
                'is_completed_status' => 0,
                'sms_template' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Waiting for Customer Response',
                'color' => '#bd4cea',
                'sort_order' => 3,
                'business_id' => 4,
                'created_at' => '2023-01-09 11:37:20',
                'updated_at' => '2023-01-09 11:37:20',
                'email_body' => NULL,
                'email_subject' => NULL,
                'is_completed_status' => 0,
                'sms_template' => NULL,
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Completed',
                'color' => '#15a00f',
                'sort_order' => 4,
                'business_id' => 4,
                'created_at' => '2023-01-09 11:38:05',
                'updated_at' => '2023-01-09 11:38:05',
                'email_body' => NULL,
                'email_subject' => NULL,
                'is_completed_status' => 1,
                'sms_template' => NULL,
            ),
        ));
        
        
    }
}