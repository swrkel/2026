<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HmsBookingLinesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('hms_booking_lines')->delete();
        
        \DB::table('hms_booking_lines')->insert(array (
            0 => 
            array (
                'id' => 1,
                'transaction_id' => 26464,
                'hms_room_id' => 1,
                'hms_room_type_id' => 1,
                'adults' => 1,
                'childrens' => 0,
                'price' => '1000.0000',
                'total_price' => '1000.0000',
                'created_at' => '2025-04-17 12:07:25',
                'updated_at' => '2025-04-17 12:07:25',
            ),
            1 => 
            array (
                'id' => 3,
                'transaction_id' => 26466,
                'hms_room_id' => 2,
                'hms_room_type_id' => 1,
                'adults' => 1,
                'childrens' => 0,
                'price' => '1000.0000',
                'total_price' => '1000.0000',
                'created_at' => '2025-04-17 12:14:31',
                'updated_at' => '2025-04-17 12:14:31',
            ),
            2 => 
            array (
                'id' => 4,
                'transaction_id' => 26467,
                'hms_room_id' => 3,
                'hms_room_type_id' => 1,
                'adults' => 1,
                'childrens' => 0,
                'price' => '1000.0000',
                'total_price' => '1000.0000',
                'created_at' => '2025-04-17 12:20:56',
                'updated_at' => '2025-04-17 12:20:56',
            ),
            3 => 
            array (
                'id' => 6,
                'transaction_id' => 26468,
                'hms_room_id' => 4,
                'hms_room_type_id' => 4,
                'adults' => 1,
                'childrens' => 0,
                'price' => '1550.0000',
                'total_price' => '1550.0000',
                'created_at' => '2025-04-17 13:04:18',
                'updated_at' => '2025-04-17 13:04:18',
            ),
            4 => 
            array (
                'id' => 7,
                'transaction_id' => 26469,
                'hms_room_id' => 5,
                'hms_room_type_id' => 1,
                'adults' => 1,
                'childrens' => 0,
                'price' => '1000.0000',
                'total_price' => '1000.0000',
                'created_at' => '2025-04-17 14:03:28',
                'updated_at' => '2025-04-17 14:03:28',
            ),
        ));
        
        
    }
}