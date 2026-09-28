<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HmsRoomTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('hms_room_types')->delete();
        
        \DB::table('hms_room_types')->insert(array (
            0 => 
            array (
                'id' => 1,
                'type' => 'Single',
                'no_of_adult' => 1,
                'no_of_child' => 0,
                'max_occupancy' => 0,
                'amenities' => NULL,
                'description' => NULL,
                'business_id' => 4,
                'created_by' => 7,
                'deleted_at' => NULL,
                'created_at' => '2025-04-16 07:26:06',
                'updated_at' => '2025-04-16 07:26:06',
            ),
            1 => 
            array (
                'id' => 2,
                'type' => 'Single',
                'no_of_adult' => 1,
                'no_of_child' => 0,
                'max_occupancy' => 0,
                'amenities' => NULL,
                'description' => NULL,
                'business_id' => 4,
                'created_by' => 7,
                'deleted_at' => NULL,
                'created_at' => '2025-04-16 07:27:13',
                'updated_at' => '2025-04-16 07:27:13',
            ),
            2 => 
            array (
                'id' => 3,
                'type' => 'Double',
                'no_of_adult' => 2,
                'no_of_child' => 1,
                'max_occupancy' => 0,
                'amenities' => NULL,
                'description' => NULL,
                'business_id' => 4,
                'created_by' => 7,
                'deleted_at' => NULL,
                'created_at' => '2025-04-17 04:47:01',
                'updated_at' => '2025-04-17 04:47:01',
            ),
            3 => 
            array (
                'id' => 4,
                'type' => 'Single',
                'no_of_adult' => 1,
                'no_of_child' => 1,
                'max_occupancy' => 0,
                'amenities' => NULL,
                'description' => NULL,
                'business_id' => 4,
                'created_by' => 7,
                'deleted_at' => NULL,
                'created_at' => '2025-04-17 12:57:44',
                'updated_at' => '2025-04-17 12:57:44',
            ),
            4 => 
            array (
                'id' => 5,
                'type' => 'Single',
                'no_of_adult' => 1,
                'no_of_child' => 0,
                'max_occupancy' => 0,
                'amenities' => NULL,
                'description' => NULL,
                'business_id' => 4,
                'created_by' => 7,
                'deleted_at' => NULL,
                'created_at' => '2025-04-17 13:56:05',
                'updated_at' => '2025-04-17 13:56:05',
            ),
        ));
        
        
    }
}