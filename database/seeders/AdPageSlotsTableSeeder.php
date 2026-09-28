<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AdPageSlotsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('ad_page_slots')->delete();
        
        \DB::table('ad_page_slots')->insert(array (
            0 => 
            array (
                'id' => 1,
                'slot' => 'Ad Slot 1',
                'slot_no' => '1',
                'ad_page_id' => 1,
                'width' => 468,
                'height' => 60,
                'created_at' => '2022-08-29 22:26:01',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            1 => 
            array (
                'id' => 2,
                'slot' => 'Ad Slot 2',
                'slot_no' => '2',
                'ad_page_id' => 1,
                'width' => 468,
                'height' => 60,
                'created_at' => '2022-08-29 22:26:01',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            2 => 
            array (
                'id' => 3,
                'slot' => 'Ad Slot 1',
                'slot_no' => '1',
                'ad_page_id' => 2,
                'width' => 468,
                'height' => 60,
                'created_at' => '2022-08-29 22:26:01',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            3 => 
            array (
                'id' => 4,
                'slot' => 'Ad Slot 2',
                'slot_no' => '2',
                'ad_page_id' => 2,
                'width' => 468,
                'height' => 60,
                'created_at' => '2022-08-29 22:26:01',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            4 => 
            array (
                'id' => 5,
                'slot' => 'Ad Slot 1',
                'slot_no' => '1',
                'ad_page_id' => 3,
                'width' => 468,
                'height' => 60,
                'created_at' => '2022-08-29 22:26:01',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            5 => 
            array (
                'id' => 6,
                'slot' => 'Ad Slot 2',
                'slot_no' => '2',
                'ad_page_id' => 3,
                'width' => 468,
                'height' => 60,
                'created_at' => '2022-08-29 22:26:01',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            6 => 
            array (
                'id' => 7,
                'slot' => 'Ad Slot 1',
                'slot_no' => '1',
                'ad_page_id' => 4,
                'width' => 468,
                'height' => 60,
                'created_at' => '2022-08-29 22:26:01',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            7 => 
            array (
                'id' => 8,
                'slot' => 'Ad Slot 2',
                'slot_no' => '2',
                'ad_page_id' => 4,
                'width' => 468,
                'height' => 60,
                'created_at' => '2022-08-29 22:26:01',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            8 => 
            array (
                'id' => 9,
                'slot' => 'Ad Slot 1',
                'slot_no' => '1',
                'ad_page_id' => 5,
                'width' => 468,
                'height' => 60,
                'created_at' => '2022-08-29 22:26:01',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            9 => 
            array (
                'id' => 10,
                'slot' => 'Ad Slot 2',
                'slot_no' => '2',
                'ad_page_id' => 5,
                'width' => 468,
                'height' => 60,
                'created_at' => '2022-08-29 22:26:01',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            10 => 
            array (
                'id' => 11,
                'slot' => 'Ad Slot 1',
                'slot_no' => '1',
                'ad_page_id' => 6,
                'width' => 576,
                'height' => 160,
                'created_at' => '2022-08-29 22:26:20',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            11 => 
            array (
                'id' => 12,
                'slot' => '3',
                'slot_no' => 'slot3',
                'ad_page_id' => 1,
                'width' => 468,
                'height' => 60,
                'created_at' => '2023-02-02 04:52:16',
                'updated_at' => '2023-02-02 04:52:16',
            ),
            12 => 
            array (
                'id' => 13,
                'slot' => '4',
                'slot_no' => 'slot4',
                'ad_page_id' => 1,
                'width' => 468,
                'height' => 60,
                'created_at' => '2023-02-02 04:52:45',
                'updated_at' => '2023-02-02 04:52:45',
            ),
            13 => 
            array (
                'id' => 14,
                'slot' => '5',
                'slot_no' => 'slot5',
                'ad_page_id' => 1,
                'width' => 468,
                'height' => 60,
                'created_at' => '2023-02-02 04:53:06',
                'updated_at' => '2023-02-02 04:53:06',
            ),
            14 => 
            array (
                'id' => 15,
                'slot' => '6',
                'slot_no' => 'slot6',
                'ad_page_id' => 1,
                'width' => 468,
                'height' => 60,
                'created_at' => '2023-02-02 04:53:25',
                'updated_at' => '2023-02-02 04:53:25',
            ),
            15 => 
            array (
                'id' => 16,
                'slot' => 'Ad Slot 1',
                'slot_no' => '3',
                'ad_page_id' => 1,
                'width' => 468,
                'height' => 60,
                'created_at' => '2023-02-02 04:58:28',
                'updated_at' => '2023-02-02 04:58:28',
            ),
            16 => 
            array (
                'id' => 17,
                'slot' => 'Ad Slot 3',
                'slot_no' => '3',
                'ad_page_id' => 1,
                'width' => 468,
                'height' => 60,
                'created_at' => '2023-02-02 04:59:48',
                'updated_at' => '2023-02-02 04:59:48',
            ),
            17 => 
            array (
                'id' => 18,
                'slot' => 'Ad Slot 4',
                'slot_no' => '4',
                'ad_page_id' => 1,
                'width' => 468,
                'height' => 60,
                'created_at' => '2023-02-02 05:03:03',
                'updated_at' => '2023-02-02 05:03:03',
            ),
            18 => 
            array (
                'id' => 19,
                'slot' => 'Ad Slot 5',
                'slot_no' => '5',
                'ad_page_id' => 1,
                'width' => 468,
                'height' => 60,
                'created_at' => '2023-02-02 05:03:26',
                'updated_at' => '2023-02-02 05:03:26',
            ),
            19 => 
            array (
                'id' => 20,
                'slot' => 'Ad Slot 6',
                'slot_no' => '6',
                'ad_page_id' => 1,
                'width' => 468,
                'height' => 60,
                'created_at' => '2023-02-02 05:03:48',
                'updated_at' => '2023-02-02 05:03:48',
            ),
        ));
        
        
    }
}