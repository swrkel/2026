<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AccountNumbersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('account_numbers')->delete();
        
        \DB::table('account_numbers')->insert(array (
            0 => 
            array (
                'id' => 2,
                'business_id' => 1,
                'account_type' => 6,
                'prefix' => 'CA',
                'account_number' => '10001',
                'created_at' => '2024-02-26 15:14:10',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            1 => 
            array (
                'id' => 3,
                'business_id' => 1,
                'account_type' => 7,
                'prefix' => 'FA',
                'account_number' => '15001',
                'created_at' => '2024-02-26 15:14:26',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            2 => 
            array (
                'id' => 4,
                'business_id' => 1,
                'account_type' => 8,
                'prefix' => 'CL',
                'account_number' => '20000',
                'created_at' => '2024-02-26 15:14:40',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            3 => 
            array (
                'id' => 5,
                'business_id' => 1,
                'account_type' => 9,
                'prefix' => 'LL',
                'account_number' => '26001',
                'created_at' => '2024-02-26 15:14:56',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            4 => 
            array (
                'id' => 6,
                'business_id' => 1,
                'account_type' => 3,
                'prefix' => 'IN',
                'account_number' => '39000',
                'created_at' => '2024-02-26 15:15:14',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            5 => 
            array (
                'id' => 7,
                'business_id' => 1,
                'account_type' => 4,
                'prefix' => 'EX',
                'account_number' => '40000',
                'created_at' => '2024-02-26 15:15:28',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            6 => 
            array (
                'id' => 8,
                'business_id' => 1,
                'account_type' => 5,
                'prefix' => 'EQ',
                'account_number' => '50000',
                'created_at' => '2024-02-26 15:15:42',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            7 => 
            array (
                'id' => 23,
                'business_id' => 4,
                'account_type' => 6,
                'prefix' => 'CA',
                'account_number' => '10001',
                'created_at' => '2025-05-28 11:46:48',
                'updated_at' => '2025-05-28 11:46:48',
            ),
            8 => 
            array (
                'id' => 24,
                'business_id' => 4,
                'account_type' => 7,
                'prefix' => 'FA',
                'account_number' => '15001',
                'created_at' => '2025-05-28 11:46:48',
                'updated_at' => '2025-05-28 11:46:48',
            ),
            9 => 
            array (
                'id' => 25,
                'business_id' => 4,
                'account_type' => 8,
                'prefix' => 'CL',
                'account_number' => '20000',
                'created_at' => '2025-05-28 11:46:48',
                'updated_at' => '2025-05-28 11:46:48',
            ),
            10 => 
            array (
                'id' => 26,
                'business_id' => 4,
                'account_type' => 9,
                'prefix' => 'LL',
                'account_number' => '26001',
                'created_at' => '2025-05-28 11:46:48',
                'updated_at' => '2025-05-28 11:46:48',
            ),
            11 => 
            array (
                'id' => 27,
                'business_id' => 4,
                'account_type' => 3,
                'prefix' => 'IN',
                'account_number' => '39000',
                'created_at' => '2025-05-28 11:46:48',
                'updated_at' => '2025-05-28 11:46:48',
            ),
            12 => 
            array (
                'id' => 28,
                'business_id' => 4,
                'account_type' => 4,
                'prefix' => 'EX',
                'account_number' => '40000',
                'created_at' => '2025-05-28 11:46:48',
                'updated_at' => '2025-05-28 11:46:48',
            ),
            13 => 
            array (
                'id' => 29,
                'business_id' => 4,
                'account_type' => 5,
                'prefix' => 'EQ',
                'account_number' => '50000',
                'created_at' => '2025-05-28 11:46:48',
                'updated_at' => '2025-05-28 11:46:48',
            ),
        ));
        
        
    }
}