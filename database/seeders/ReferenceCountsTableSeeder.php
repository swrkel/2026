<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ReferenceCountsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('reference_counts')->delete();
        
        \DB::table('reference_counts')->insert(array (
            0 => 
            array (
                'id' => 1,
                'ref_type' => 'business_location',
                'ref_count' => 1,
                'business_id' => 2,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
            ),
            1 => 
            array (
                'id' => 2,
                'ref_type' => 'contacts',
                'ref_count' => 1,
                'business_id' => 2,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
            ),
            2 => 
            array (
                'id' => 3,
                'ref_type' => 'business_location',
                'ref_count' => 1,
                'business_id' => 3,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
            ),
            3 => 
            array (
                'id' => 4,
                'ref_type' => 'contacts',
                'ref_count' => 1,
                'business_id' => 3,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
            ),
            4 => 
            array (
                'id' => 5,
                'ref_type' => 'contacts',
                'ref_count' => 3355,
                'business_id' => 4,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2025-05-28 13:23:59',
            ),
            5 => 
            array (
                'id' => 6,
                'ref_type' => 'business_location',
                'ref_count' => 1,
                'business_id' => 4,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            6 => 
            array (
                'id' => 7,
                'ref_type' => 'opening_balance',
                'ref_count' => 22,
                'business_id' => 4,
                'created_at' => '2024-02-27 03:39:17',
                'updated_at' => '2024-02-27 12:39:00',
            ),
            7 => 
            array (
                'id' => 8,
                'ref_type' => 'username',
                'ref_count' => 5,
                'business_id' => 4,
                'created_at' => '2024-02-28 04:17:49',
                'updated_at' => '2024-09-12 11:56:49',
            ),
            8 => 
            array (
                'id' => 9,
                'ref_type' => 'purchase',
                'ref_count' => 1258,
                'business_id' => 4,
                'created_at' => '2024-02-28 09:05:13',
                'updated_at' => '2025-05-07 09:59:38',
            ),
            9 => 
            array (
                'id' => 10,
                'ref_type' => 'expense',
                'ref_count' => 548,
                'business_id' => 4,
                'created_at' => '2024-02-28 10:43:56',
                'updated_at' => '2025-05-05 08:12:57',
            ),
            10 => 
            array (
                'id' => 11,
                'ref_type' => 'expense_payment',
                'ref_count' => 550,
                'business_id' => 4,
                'created_at' => '2024-02-28 10:44:46',
                'updated_at' => '2025-05-05 08:12:57',
            ),
            11 => 
            array (
                'id' => 12,
                'ref_type' => 'sell_payment',
                'ref_count' => 6333,
                'business_id' => 4,
                'created_at' => '2024-02-28 12:12:56',
                'updated_at' => '2025-05-29 14:51:35',
            ),
            12 => 
            array (
                'id' => 13,
                'ref_type' => 'purchase_payment',
                'ref_count' => 938,
                'business_id' => 4,
                'created_at' => '2024-03-04 06:36:04',
                'updated_at' => '2025-05-07 09:59:38',
            ),
            13 => 
            array (
                'id' => 14,
                'ref_type' => 'advance_payment',
                'ref_count' => 1,
                'business_id' => 4,
                'created_at' => '2024-03-04 09:24:56',
                'updated_at' => '2024-03-04 09:24:56',
            ),
            14 => 
            array (
                'id' => 15,
                'ref_type' => 'stock_adjustment',
                'ref_count' => 4,
                'business_id' => 4,
                'created_at' => '2024-03-11 09:42:58',
                'updated_at' => '2024-07-12 13:00:08',
            ),
            15 => 
            array (
                'id' => 16,
                'ref_type' => 'shortage_recover',
                'ref_count' => 1,
                'business_id' => 4,
                'created_at' => '2024-06-06 15:25:13',
                'updated_at' => '2024-06-06 15:25:13',
            ),
            16 => 
            array (
                'id' => 17,
                'ref_type' => 'security_deposit',
                'ref_count' => 1,
                'business_id' => 4,
                'created_at' => '2024-06-27 12:07:24',
                'updated_at' => '2024-06-27 12:07:24',
            ),
            17 => 
            array (
                'id' => 18,
                'ref_type' => 'quotation_no',
                'ref_count' => 2,
                'business_id' => 4,
                'created_at' => '2024-08-28 10:48:53',
                'updated_at' => '2024-08-28 11:04:11',
            ),
            18 => 
            array (
                'id' => 22,
                'ref_type' => 'hms_booking',
                'ref_count' => 5,
                'business_id' => 4,
                'created_at' => '2025-04-17 12:07:25',
                'updated_at' => '2025-04-17 14:03:28',
            ),
            19 => 
            array (
                'id' => 23,
                'ref_type' => 'job_sheet',
                'ref_count' => 1,
                'business_id' => 4,
                'created_at' => '2025-05-28 12:18:33',
                'updated_at' => '2025-05-28 12:18:33',
            ),
        ));
        
        
    }
}