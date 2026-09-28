<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DefaultAccountGroupsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('default_account_groups')->delete();
        
        \DB::table('default_account_groups')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 1,
                'name' => 'Raw Material Account',
                'account_type_id' => 6,
                'note' => NULL,
                'show_status' => 0,
                'created_at' => '2020-06-19 08:27:31',
                'updated_at' => '2020-06-19 08:27:31',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 1,
                'name' => 'Finished Goods Account',
                'account_type_id' => 6,
                'note' => NULL,
                'show_status' => 0,
                'created_at' => '2020-06-19 08:27:54',
                'updated_at' => '2020-06-19 08:27:54',
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 1,
                'name' => 'Other Stocks',
                'account_type_id' => 6,
                'note' => NULL,
                'show_status' => 0,
                'created_at' => '2020-06-19 08:28:09',
                'updated_at' => '2020-06-19 08:28:09',
            ),
            3 => 
            array (
                'id' => 4,
                'business_id' => 1,
                'name' => 'Bank Account',
                'account_type_id' => 6,
                'note' => NULL,
                'show_status' => 1,
                'created_at' => '2020-06-19 08:28:27',
                'updated_at' => '2023-06-22 14:49:38',
            ),
            4 => 
            array (
                'id' => 5,
                'business_id' => 1,
                'name' => 'Cash Account',
                'account_type_id' => 6,
                'note' => NULL,
                'show_status' => 0,
                'created_at' => '2020-06-19 08:28:44',
                'updated_at' => '2020-06-19 08:28:44',
            ),
            5 => 
            array (
                'id' => 6,
                'business_id' => 1,
            'name' => 'Cheques in Hand (Customer\'s)',
                'account_type_id' => 6,
                'note' => NULL,
                'show_status' => 0,
                'created_at' => '2020-06-19 08:29:01',
                'updated_at' => '2020-06-19 08:29:01',
            ),
            6 => 
            array (
                'id' => 7,
                'business_id' => 1,
                'name' => 'Card',
                'account_type_id' => 6,
                'note' => NULL,
                'show_status' => 1,
                'created_at' => '2020-06-19 08:31:50',
                'updated_at' => '2023-06-22 15:12:28',
            ),
            7 => 
            array (
                'id' => 8,
                'business_id' => 1,
                'name' => 'COGS Account Group',
                'account_type_id' => 4,
                'note' => NULL,
                'show_status' => 1,
                'created_at' => '2020-06-19 08:32:06',
                'updated_at' => '2023-06-22 15:12:37',
            ),
            8 => 
            array (
                'id' => 9,
                'business_id' => 1,
                'name' => 'Sales Income Group',
                'account_type_id' => 3,
                'note' => 'na',
                'show_status' => 1,
                'created_at' => '2020-06-26 12:22:14',
                'updated_at' => '2023-06-24 16:15:53',
            ),
            9 => 
            array (
                'id' => 10,
                'business_id' => 1,
                'name' => 'Direct Expense',
                'account_type_id' => 4,
                'note' => NULL,
                'show_status' => 0,
                'created_at' => '2020-07-10 01:53:35',
                'updated_at' => '2020-07-10 01:53:35',
            ),
            10 => 
            array (
                'id' => 11,
                'business_id' => 1,
                'name' => 'CPC',
                'account_type_id' => 6,
                'note' => NULL,
                'show_status' => 0,
                'created_at' => '2023-04-16 16:50:51',
                'updated_at' => '2023-04-16 16:50:51',
            ),
            11 => 
            array (
                'id' => 12,
                'business_id' => 1,
                'name' => 'Goods in Transit',
                'account_type_id' => 6,
                'note' => NULL,
                'show_status' => 1,
                'created_at' => '2023-05-17 05:42:15',
                'updated_at' => '2023-06-25 08:32:17',
            ),
            12 => 
            array (
                'id' => 13,
                'business_id' => 1,
                'name' => 'Credit Sales',
                'account_type_id' => 6,
                'note' => NULL,
                'show_status' => 0,
                'created_at' => '2023-08-30 11:26:33',
                'updated_at' => '2023-08-30 11:26:33',
            ),
            13 => 
            array (
                'id' => 14,
                'business_id' => 1,
                'name' => 'Loans Given',
                'account_type_id' => 6,
                'note' => NULL,
                'show_status' => 1,
                'created_at' => '2023-10-12 18:46:24',
                'updated_at' => '2024-02-26 15:16:28',
            ),
            14 => 
            array (
                'id' => 15,
                'business_id' => 1,
                'name' => 'Loan Taken',
                'account_type_id' => 9,
                'note' => NULL,
                'show_status' => 1,
                'created_at' => '2023-11-05 06:20:34',
                'updated_at' => '2024-02-26 15:16:15',
            ),
            15 => 
            array (
                'id' => 18,
                'business_id' => 1,
                'name' => 'Owners Drawings',
                'account_type_id' => 5,
                'note' => NULL,
                'show_status' => 1,
                'created_at' => '2023-11-21 10:06:58',
                'updated_at' => '2024-02-27 04:13:02',
            ),
            16 => 
            array (
                'id' => 21,
                'business_id' => 1,
                'name' => 'Own Cards',
                'account_type_id' => 5,
                'note' => NULL,
                'show_status' => 0,
                'created_at' => '2024-01-22 17:18:36',
                'updated_at' => '2024-01-22 17:18:36',
            ),
            17 => 
            array (
                'id' => 22,
                'business_id' => 1,
                'name' => 'Indirect Expense',
                'account_type_id' => 4,
                'note' => NULL,
                'show_status' => 0,
                'created_at' => '2024-01-25 02:57:12',
                'updated_at' => '2024-01-25 02:57:12',
            ),
            18 => 
            array (
                'id' => 24,
                'business_id' => 4,
                'name' => 'Owners Drawings',
                'account_type_id' => 0,
                'note' => NULL,
                'show_status' => 0,
                'created_at' => '2024-02-27 04:14:48',
                'updated_at' => '2024-02-27 04:14:48',
            ),
        ));
        
        
    }
}