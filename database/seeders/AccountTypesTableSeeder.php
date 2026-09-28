<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AccountTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('account_types')->delete();
        
        \DB::table('account_types')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Assets',
                'parent_account_type_id' => NULL,
                'business_id' => 1,
                'default_account_type_id' => 1,
                'created_at' => '2020-06-19 08:24:38',
                'updated_at' => '2020-06-19 08:24:38',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Liabilities',
                'parent_account_type_id' => NULL,
                'business_id' => 1,
                'default_account_type_id' => 2,
                'created_at' => '2020-06-19 08:24:53',
                'updated_at' => '2020-06-19 08:24:53',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Income',
                'parent_account_type_id' => NULL,
                'business_id' => 1,
                'default_account_type_id' => 3,
                'created_at' => '2020-06-19 08:25:06',
                'updated_at' => '2020-06-19 08:25:06',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Expenses',
                'parent_account_type_id' => NULL,
                'business_id' => 1,
                'default_account_type_id' => 4,
                'created_at' => '2020-06-19 08:25:17',
                'updated_at' => '2020-06-19 08:25:17',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'Equity',
                'parent_account_type_id' => NULL,
                'business_id' => 1,
                'default_account_type_id' => 5,
                'created_at' => '2020-06-19 08:25:29',
                'updated_at' => '2020-06-19 08:25:29',
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'Current Assets',
                'parent_account_type_id' => 1,
                'business_id' => 1,
                'default_account_type_id' => 6,
                'created_at' => '2020-06-19 08:25:47',
                'updated_at' => '2020-06-19 08:25:47',
            ),
            6 => 
            array (
                'id' => 7,
                'name' => 'Fixed Assets',
                'parent_account_type_id' => 1,
                'business_id' => 1,
                'default_account_type_id' => 7,
                'created_at' => '2020-06-19 08:26:02',
                'updated_at' => '2020-06-19 08:26:02',
            ),
            7 => 
            array (
                'id' => 8,
                'name' => 'Current Liabilities',
                'parent_account_type_id' => 2,
                'business_id' => 1,
                'default_account_type_id' => 8,
                'created_at' => '2020-06-19 08:26:20',
                'updated_at' => '2020-06-19 08:26:20',
            ),
            8 => 
            array (
                'id' => 9,
                'name' => 'Long Term Liabilities',
                'parent_account_type_id' => 2,
                'business_id' => 1,
                'default_account_type_id' => 9,
                'created_at' => '2020-06-19 08:26:35',
                'updated_at' => '2020-06-19 08:26:35',
            ),
            9 => 
            array (
                'id' => 28,
                'name' => 'Assets',
                'parent_account_type_id' => NULL,
                'business_id' => 4,
                'default_account_type_id' => 1,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            10 => 
            array (
                'id' => 29,
                'name' => 'Liabilities',
                'parent_account_type_id' => NULL,
                'business_id' => 4,
                'default_account_type_id' => 2,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            11 => 
            array (
                'id' => 30,
                'name' => 'Income',
                'parent_account_type_id' => NULL,
                'business_id' => 4,
                'default_account_type_id' => 3,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            12 => 
            array (
                'id' => 31,
                'name' => 'Expenses',
                'parent_account_type_id' => NULL,
                'business_id' => 4,
                'default_account_type_id' => 4,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            13 => 
            array (
                'id' => 32,
                'name' => 'Equity',
                'parent_account_type_id' => NULL,
                'business_id' => 4,
                'default_account_type_id' => 5,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            14 => 
            array (
                'id' => 33,
                'name' => 'Current Assets',
                'parent_account_type_id' => 28,
                'business_id' => 4,
                'default_account_type_id' => 6,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            15 => 
            array (
                'id' => 34,
                'name' => 'Fixed Assets',
                'parent_account_type_id' => 28,
                'business_id' => 4,
                'default_account_type_id' => 7,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            16 => 
            array (
                'id' => 35,
                'name' => 'Current Liabilities',
                'parent_account_type_id' => 29,
                'business_id' => 4,
                'default_account_type_id' => 8,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            17 => 
            array (
                'id' => 36,
                'name' => 'Long Term Liabilities',
                'parent_account_type_id' => 29,
                'business_id' => 4,
                'default_account_type_id' => 9,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
        ));
        
        
    }
}