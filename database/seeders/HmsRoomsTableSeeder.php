<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HmsRoomsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('hms_rooms')->delete();
        
        \DB::table('hms_rooms')->insert(array (
            0 => 
            array (
                'id' => 1,
                'hms_room_type_id' => 1,
                'room_number' => '101',
                'deleted_at' => NULL,
                'created_at' => '2025-04-16 07:26:06',
                'updated_at' => '2025-04-16 07:26:06',
            ),
            1 => 
            array (
                'id' => 2,
                'hms_room_type_id' => 2,
                'room_number' => '102',
                'deleted_at' => NULL,
                'created_at' => '2025-04-16 07:27:13',
                'updated_at' => '2025-04-16 07:27:13',
            ),
            2 => 
            array (
                'id' => 3,
                'hms_room_type_id' => 3,
                'room_number' => '201',
                'deleted_at' => NULL,
                'created_at' => '2025-04-17 04:47:01',
                'updated_at' => '2025-04-17 04:47:01',
            ),
            3 => 
            array (
                'id' => 4,
                'hms_room_type_id' => 4,
                'room_number' => '110',
                'deleted_at' => NULL,
                'created_at' => '2025-04-17 12:57:44',
                'updated_at' => '2025-04-17 12:57:44',
            ),
            4 => 
            array (
                'id' => 5,
                'hms_room_type_id' => 5,
                'room_number' => '111',
                'deleted_at' => NULL,
                'created_at' => '2025-04-17 13:56:05',
                'updated_at' => '2025-04-17 13:56:05',
            ),
        ));
        
        
    }
}