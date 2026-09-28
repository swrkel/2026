<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ReviewedChangesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('reviewed_changes')->delete();
        
        \DB::table('reviewed_changes')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'date' => '2024-03-31',
                'created_at' => '2024-04-06 08:18:50',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'date' => '2024-04-19',
                'created_at' => '2024-05-06 06:40:43',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 4,
                'date' => '2024-04-22',
                'created_at' => '2024-05-15 12:03:13',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            3 => 
            array (
                'id' => 4,
                'business_id' => 4,
                'date' => '2024-04-08',
                'created_at' => '2024-05-15 12:05:40',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            4 => 
            array (
                'id' => 5,
                'business_id' => 4,
                'date' => '2024-05-20',
                'created_at' => '2024-05-27 12:26:23',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            5 => 
            array (
                'id' => 6,
                'business_id' => 4,
                'date' => '2024-06-03',
                'created_at' => '2024-06-12 10:22:35',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            6 => 
            array (
                'id' => 7,
                'business_id' => 4,
                'date' => '2024-05-06',
                'created_at' => '2024-06-19 06:12:14',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            7 => 
            array (
                'id' => 8,
                'business_id' => 4,
                'date' => '2024-06-04',
                'created_at' => '2024-06-19 06:19:29',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            8 => 
            array (
                'id' => 9,
                'business_id' => 4,
                'date' => '2024-06-05',
                'created_at' => '2024-06-19 06:22:08',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            9 => 
            array (
                'id' => 10,
                'business_id' => 4,
                'date' => '2024-05-31',
                'created_at' => '2024-06-19 06:25:14',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            10 => 
            array (
                'id' => 11,
                'business_id' => 4,
                'date' => '2024-07-12',
                'created_at' => '2024-07-25 11:54:09',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            11 => 
            array (
                'id' => 12,
                'business_id' => 4,
                'date' => '2024-07-13',
                'created_at' => '2024-07-25 11:55:39',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            12 => 
            array (
                'id' => 13,
                'business_id' => 4,
                'date' => '2025-01-02',
                'created_at' => '2025-01-21 08:28:48',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            13 => 
            array (
                'id' => 14,
                'business_id' => 4,
                'date' => '2025-03-26',
                'created_at' => '2025-04-02 12:20:08',
                'updated_at' => '0000-00-00 00:00:00',
            ),
        ));
        
        
    }
}