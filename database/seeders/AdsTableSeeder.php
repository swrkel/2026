<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AdsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('ads')->delete();
        
        \DB::table('ads')->insert(array (
            0 => 
            array (
                'id' => 10,
                'ad_id' => '63db36c9016f6',
                'ad_page_id' => 1,
                'ad_page_slot_id' => 20,
                'status' => 1,
                'code' => '2023-106',
                'client_name' => 'syzygy',
                'start_date' => '2023-02-02',
                'end_date' => '2023-02-28',
                'amount' => '1',
                'created_at' => '2023-02-01 19:30:00',
                'updated_at' => '2023-02-02 05:06:33',
                'content' => '/storage/ads/2023-106.jpg',
                'link' => NULL,
            ),
            1 => 
            array (
                'id' => 11,
                'ad_id' => '63db37698d203',
                'ad_page_id' => 1,
                'ad_page_slot_id' => 1,
                'status' => 1,
                'code' => '2023-107',
                'client_name' => 'syzygy',
                'start_date' => '2023-02-02',
                'end_date' => '2023-02-28',
                'amount' => '1',
                'created_at' => '2023-02-01 19:30:00',
                'updated_at' => '2023-02-02 05:09:13',
                'content' => '/storage/ads/2023-107.jpg',
                'link' => NULL,
            ),
            2 => 
            array (
                'id' => 12,
                'ad_id' => '63db3804ce431',
                'ad_page_id' => 1,
                'ad_page_slot_id' => 2,
                'status' => 1,
                'code' => '2023-108',
                'client_name' => 'syzygy',
                'start_date' => '2023-02-02',
                'end_date' => '2023-02-28',
                'amount' => '1',
                'created_at' => '2023-02-01 19:30:00',
                'updated_at' => '2023-02-02 05:11:48',
                'content' => '/storage/ads/2023-108.jpg',
                'link' => NULL,
            ),
            3 => 
            array (
                'id' => 13,
                'ad_id' => '63db387a7f883',
                'ad_page_id' => 1,
                'ad_page_slot_id' => 17,
                'status' => 1,
                'code' => '2023-109',
                'client_name' => 'syzygy',
                'start_date' => '2023-02-02',
                'end_date' => '2023-02-28',
                'amount' => '1',
                'created_at' => '2023-02-01 19:30:00',
                'updated_at' => '2023-02-02 05:13:46',
                'content' => '/storage/ads/2023-109.jpg',
                'link' => NULL,
            ),
            4 => 
            array (
                'id' => 15,
                'ad_id' => '63db391e37188',
                'ad_page_id' => 1,
                'ad_page_slot_id' => 18,
                'status' => 1,
                'code' => '2023-110',
                'client_name' => 'syzygy',
                'start_date' => '2023-02-02',
                'end_date' => '2023-02-28',
                'amount' => '1',
                'created_at' => '2023-02-01 19:30:00',
                'updated_at' => '2023-02-02 05:16:30',
                'content' => '/storage/ads/2023-110.jpg',
                'link' => NULL,
            ),
            5 => 
            array (
                'id' => 16,
                'ad_id' => '63db396793096',
                'ad_page_id' => 1,
                'ad_page_slot_id' => 19,
                'status' => 1,
                'code' => '2023-111',
                'client_name' => 'syzygy',
                'start_date' => '2023-02-02',
                'end_date' => '2023-02-28',
                'amount' => '1',
                'created_at' => '2023-02-01 19:30:00',
                'updated_at' => '2023-02-02 05:17:43',
                'content' => '/storage/ads/2023-111.jpg',
                'link' => NULL,
            ),
        ));
        
        
    }
}