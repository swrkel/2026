<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ReviewedChangesDescriptionTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('reviewed_changes_description')->delete();
        
        \DB::table('reviewed_changes_description')->insert(array (
            0 => 
            array (
                'id' => 1,
                'review_id' => 0,
                'created_by' => 7,
                'description' => 'Deleted an expense: EP2024/0037',
                'created_at' => '2024-04-06 08:18:50',
                'module' => 'expense',
            ),
            1 => 
            array (
                'id' => 2,
                'review_id' => 0,
                'created_by' => 7,
                'description' => 'Deleted an expense: EP2024/0062',
                'created_at' => '2024-05-06 06:40:43',
                'module' => 'expense',
            ),
            2 => 
            array (
                'id' => 3,
                'review_id' => 0,
                'created_by' => 7,
                'description' => 'Deleted an expense: EP2024/0088',
                'created_at' => '2024-05-15 12:03:13',
                'module' => 'expense',
            ),
            3 => 
            array (
                'id' => 4,
                'review_id' => 0,
                'created_by' => 7,
                'description' => 'Deleted an expense: EP2024/0087',
                'created_at' => '2024-05-15 12:03:24',
                'module' => 'expense',
            ),
            4 => 
            array (
                'id' => 5,
                'review_id' => 0,
                'created_by' => 7,
                'description' => 'Deleted an expense: EP2024/0089',
                'created_at' => '2024-05-15 12:05:40',
                'module' => 'expense',
            ),
            5 => 
            array (
                'id' => 6,
                'review_id' => 0,
                'created_by' => 7,
                'description' => 'Deleted an expense: EP2024/0119',
                'created_at' => '2024-05-27 12:26:23',
                'module' => 'expense',
            ),
            6 => 
            array (
                'id' => 7,
                'review_id' => 0,
                'created_by' => 7,
                'description' => 'Deleted an expense: EP2024/0131',
                'created_at' => '2024-06-12 10:22:35',
                'module' => 'expense',
            ),
            7 => 
            array (
                'id' => 8,
                'review_id' => 0,
                'created_by' => 7,
                'description' => 'Deleted an expense: EP2024/0156',
                'created_at' => '2024-06-19 06:11:47',
                'module' => 'expense',
            ),
            8 => 
            array (
                'id' => 9,
                'review_id' => 0,
                'created_by' => 7,
                'description' => 'Deleted an expense: EP2024/0157',
                'created_at' => '2024-06-19 06:12:14',
                'module' => 'expense',
            ),
            9 => 
            array (
                'id' => 10,
                'review_id' => 0,
                'created_by' => 7,
                'description' => 'Deleted an expense: EP2024/0137',
                'created_at' => '2024-06-19 06:19:29',
                'module' => 'expense',
            ),
            10 => 
            array (
                'id' => 11,
                'review_id' => 0,
                'created_by' => 7,
                'description' => 'Deleted an expense: EP2024/0160',
                'created_at' => '2024-06-19 06:22:08',
                'module' => 'expense',
            ),
            11 => 
            array (
                'id' => 12,
                'review_id' => 0,
                'created_by' => 7,
                'description' => 'Deleted an expense: EP2024/0155',
                'created_at' => '2024-06-19 06:22:14',
                'module' => 'expense',
            ),
            12 => 
            array (
                'id' => 13,
                'review_id' => 0,
                'created_by' => 7,
                'description' => 'Deleted an expense: EP2024/0152',
                'created_at' => '2024-06-19 06:25:14',
                'module' => 'expense',
            ),
            13 => 
            array (
                'id' => 14,
                'review_id' => 0,
                'created_by' => 7,
                'description' => 'Deleted an expense: EP2024/0201',
                'created_at' => '2024-07-25 11:54:09',
                'module' => 'expense',
            ),
            14 => 
            array (
                'id' => 15,
                'review_id' => 0,
                'created_by' => 7,
                'description' => 'Deleted an expense: EP2024/0202',
                'created_at' => '2024-07-25 11:55:39',
                'module' => 'expense',
            ),
            15 => 
            array (
                'id' => 16,
                'review_id' => 0,
                'created_by' => 7,
                'description' => 'Deleted an expense: EP2025/0418',
                'created_at' => '2025-01-21 08:28:48',
                'module' => 'expense',
            ),
            16 => 
            array (
                'id' => 17,
                'review_id' => 0,
                'created_by' => 7,
                'description' => 'Deleted an expense: EP2025/0521',
                'created_at' => '2025-04-02 12:20:08',
                'module' => 'expense',
            ),
        ));
        
        
    }
}