<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HmsRoomUnavailablesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('hms_room_unavailables')->delete();
        
        \DB::table('hms_room_unavailables')->insert(array (
            0 => 
            array (
                'id' => 1,
                'hms_rooms_id' => 1,
                'date_from' => '2025-04-17',
                'date_to' => '2025-04-17',
                'unavailable_type' => 'single',
                'created_at' => '2025-04-17 12:07:25',
                'updated_at' => '2025-04-17 12:07:25',
            ),
            1 => 
            array (
                'id' => 3,
                'hms_rooms_id' => 2,
                'date_from' => '2025-04-17',
                'date_to' => '2025-04-17',
                'unavailable_type' => 'single',
                'created_at' => '2025-04-17 12:14:31',
                'updated_at' => '2025-04-17 12:14:31',
            ),
            2 => 
            array (
                'id' => 4,
                'hms_rooms_id' => 3,
                'date_from' => '2025-04-17',
                'date_to' => '2025-04-17',
                'unavailable_type' => 'single',
                'created_at' => '2025-04-17 12:20:56',
                'updated_at' => '2025-04-17 12:20:56',
            ),
            3 => 
            array (
                'id' => 5,
                'hms_rooms_id' => 4,
                'date_from' => '2025-04-17',
                'date_to' => '2025-04-17',
                'unavailable_type' => 'single',
                'created_at' => '2025-04-17 13:03:46',
                'updated_at' => '2025-04-17 13:03:46',
            ),
            4 => 
            array (
                'id' => 6,
                'hms_rooms_id' => 5,
                'date_from' => '2025-04-17',
                'date_to' => '2025-04-17',
                'unavailable_type' => 'single',
                'created_at' => '2025-04-17 14:03:28',
                'updated_at' => '2025-04-17 14:03:28',
            ),
        ));
        
        
    }
}