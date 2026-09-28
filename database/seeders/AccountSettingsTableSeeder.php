<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AccountSettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('account_settings')->delete();
        
        \DB::table('account_settings')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'date' => '2024-02-25',
                'account_id' => 188,
                'group_id' => 61,
                'amount' => '-353868.210000',
                'at_asset_id' => 51,
                'at_obe_id' => 52,
                'created_by' => 7,
                'created_at' => '2024-02-27 03:46:25',
                'updated_at' => '2024-02-27 03:46:25',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'date' => '2024-02-25',
                'account_id' => 189,
                'group_id' => 61,
                'amount' => '-51558.110000',
                'at_asset_id' => 53,
                'at_obe_id' => 54,
                'created_by' => 7,
                'created_at' => '2024-02-27 03:47:28',
                'updated_at' => '2024-02-27 03:47:28',
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 4,
                'date' => '2024-02-25',
                'account_id' => 190,
                'group_id' => 61,
                'amount' => '168784.530000',
                'at_asset_id' => 55,
                'at_obe_id' => 56,
                'created_by' => 7,
                'created_at' => '2024-02-27 03:47:56',
                'updated_at' => '2024-02-27 03:47:56',
            ),
            3 => 
            array (
                'id' => 4,
                'business_id' => 4,
                'date' => '2024-02-25',
                'account_id' => 191,
                'group_id' => 61,
                'amount' => '-10903362.340000',
                'at_asset_id' => 57,
                'at_obe_id' => 58,
                'created_by' => 7,
                'created_at' => '2024-02-27 03:48:49',
                'updated_at' => '2024-02-27 03:48:49',
            ),
            4 => 
            array (
                'id' => 5,
                'business_id' => 4,
                'date' => '2024-02-25',
                'account_id' => 192,
                'group_id' => 61,
                'amount' => '1723554.100000',
                'at_asset_id' => 59,
                'at_obe_id' => 60,
                'created_by' => 7,
                'created_at' => '2024-02-27 03:49:40',
                'updated_at' => '2024-02-27 03:49:40',
            ),
            5 => 
            array (
                'id' => 6,
                'business_id' => 4,
                'date' => '2024-02-25',
                'account_id' => 137,
                'group_id' => 62,
                'amount' => '10643770.000000',
                'at_asset_id' => 273,
                'at_obe_id' => 274,
                'created_by' => 7,
                'created_at' => '2024-02-27 12:36:34',
                'updated_at' => '2024-02-27 12:36:34',
            ),
            6 => 
            array (
                'id' => 7,
                'business_id' => 4,
                'date' => '2024-02-25',
                'account_id' => 194,
                'group_id' => 64,
                'amount' => '11971122.850000',
                'at_asset_id' => 275,
                'at_obe_id' => 276,
                'created_by' => 7,
                'created_at' => '2024-02-27 12:37:06',
                'updated_at' => '2024-02-27 12:37:06',
            ),
            7 => 
            array (
                'id' => 8,
                'business_id' => 4,
                'date' => '2024-02-25',
                'account_id' => 193,
                'group_id' => 64,
                'amount' => '1508190.000000',
                'at_asset_id' => 277,
                'at_obe_id' => 278,
                'created_by' => 7,
                'created_at' => '2024-02-27 12:37:39',
                'updated_at' => '2024-02-27 12:37:39',
            ),
            8 => 
            array (
                'id' => 9,
                'business_id' => 4,
                'date' => '2024-02-25',
                'account_id' => 210,
                'group_id' => 64,
                'amount' => '1397796.770000',
                'at_asset_id' => 279,
                'at_obe_id' => 280,
                'created_by' => 7,
                'created_at' => '2024-02-27 12:38:04',
                'updated_at' => '2024-02-27 12:38:04',
            ),
            9 => 
            array (
                'id' => 10,
                'business_id' => 4,
                'date' => '2024-02-25',
                'account_id' => 211,
                'group_id' => 64,
                'amount' => '635918.880000',
                'at_asset_id' => 281,
                'at_obe_id' => 282,
                'created_by' => 7,
                'created_at' => '2024-02-27 12:38:23',
                'updated_at' => '2024-02-27 12:38:23',
            ),
            10 => 
            array (
                'id' => 11,
                'business_id' => 4,
                'date' => '2024-02-25',
                'account_id' => 215,
                'group_id' => 71,
                'amount' => '41500.000000',
                'at_asset_id' => 363,
                'at_obe_id' => 364,
                'created_by' => 38,
                'created_at' => '2024-02-28 11:01:20',
                'updated_at' => '2024-02-28 11:01:20',
            ),
            11 => 
            array (
                'id' => 12,
                'business_id' => 4,
                'date' => '2024-02-25',
                'account_id' => 138,
                'group_id' => 62,
                'amount' => '2904.520000',
                'at_asset_id' => 412,
                'at_obe_id' => 413,
                'created_by' => 7,
                'created_at' => '2024-02-28 12:11:16',
                'updated_at' => '2024-02-28 12:11:16',
            ),
            12 => 
            array (
                'id' => 13,
                'business_id' => 4,
                'date' => '2024-02-25',
                'account_id' => 139,
                'group_id' => 63,
                'amount' => '63841.840000',
                'at_asset_id' => 414,
                'at_obe_id' => 415,
                'created_by' => 7,
                'created_at' => '2024-02-28 12:12:56',
                'updated_at' => '2024-02-28 12:12:56',
            ),
            13 => 
            array (
                'id' => 14,
                'business_id' => 4,
                'date' => '2024-02-26',
                'account_id' => 139,
                'group_id' => 63,
                'amount' => '222468.960000',
                'at_asset_id' => 420,
                'at_obe_id' => 421,
                'created_by' => 7,
                'created_at' => '2024-02-28 12:42:25',
                'updated_at' => '2024-02-28 12:42:25',
            ),
            14 => 
            array (
                'id' => 15,
                'business_id' => 4,
                'date' => '2024-03-01',
                'account_id' => 234,
                'group_id' => 72,
                'amount' => '9000000.000000',
                'at_asset_id' => 11076,
                'at_obe_id' => 11077,
                'created_by' => 7,
                'created_at' => '2024-05-15 08:23:07',
                'updated_at' => '2024-05-15 08:23:07',
            ),
            15 => 
            array (
                'id' => 16,
                'business_id' => 4,
                'date' => '2024-03-01',
                'account_id' => 235,
                'group_id' => 72,
                'amount' => '1759000.000000',
                'at_asset_id' => 11078,
                'at_obe_id' => 11079,
                'created_by' => 7,
                'created_at' => '2024-05-15 08:23:33',
                'updated_at' => '2024-05-15 08:23:33',
            ),
            16 => 
            array (
                'id' => 17,
                'business_id' => 4,
                'date' => '2024-03-01',
                'account_id' => 236,
                'group_id' => 72,
                'amount' => '7700000.000000',
                'at_asset_id' => 11080,
                'at_obe_id' => 11081,
                'created_by' => 7,
                'created_at' => '2024-05-15 08:24:06',
                'updated_at' => '2024-05-15 08:24:06',
            ),
        ));
        
        
    }
}