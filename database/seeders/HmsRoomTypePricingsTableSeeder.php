<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HmsRoomTypePricingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('hms_room_type_pricings')->delete();
        
        \DB::table('hms_room_type_pricings')->insert(array (
            0 => 
            array (
                'id' => 1,
                'hms_room_type_id' => 1,
                'season_type' => 'default',
                'default_price_per_night' => NULL,
                'adults' => NULL,
                'childrens' => NULL,
                'price_monday' => 1000.0,
                'price_tuesday' => 1000.0,
                'price_wednesday' => 1000.0,
                'price_thursday' => 1000.0,
                'price_friday' => 1000.0,
                'price_saturday' => 1000.0,
                'price_sunday' => 1000.0,
                'created_at' => '2025-04-16 07:26:36',
                'updated_at' => '2025-04-16 07:26:36',
            ),
            1 => 
            array (
                'id' => 2,
                'hms_room_type_id' => 3,
                'season_type' => 'default',
                'default_price_per_night' => NULL,
                'adults' => NULL,
                'childrens' => NULL,
                'price_monday' => 2000.0,
                'price_tuesday' => 3000.0,
                'price_wednesday' => 3000.0,
                'price_thursday' => 3000.0,
                'price_friday' => 3000.0,
                'price_saturday' => 3000.0,
                'price_sunday' => 3000.0,
                'created_at' => '2025-04-17 04:47:33',
                'updated_at' => '2025-04-17 04:47:33',
            ),
            2 => 
            array (
                'id' => 3,
                'hms_room_type_id' => 4,
                'season_type' => 'default',
                'default_price_per_night' => NULL,
                'adults' => NULL,
                'childrens' => NULL,
                'price_monday' => 1300.0,
                'price_tuesday' => 1550.0,
                'price_wednesday' => 1550.0,
                'price_thursday' => 1550.0,
                'price_friday' => 1550.0,
                'price_saturday' => 1550.0,
                'price_sunday' => 1550.0,
                'created_at' => '2025-04-17 12:58:14',
                'updated_at' => '2025-04-17 12:58:14',
            ),
            3 => 
            array (
                'id' => 4,
                'hms_room_type_id' => 5,
                'season_type' => 'default',
                'default_price_per_night' => NULL,
                'adults' => NULL,
                'childrens' => NULL,
                'price_monday' => 111.0,
                'price_tuesday' => 222.0,
                'price_wednesday' => 222.0,
                'price_thursday' => 222.0,
                'price_friday' => 222.0,
                'price_saturday' => 222.0,
                'price_sunday' => 222.0,
                'created_at' => '2025-04-17 13:56:32',
                'updated_at' => '2025-04-17 14:02:47',
            ),
        ));
        
        
    }
}