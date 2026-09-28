<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserStorePermissionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('user_store_permissions')->delete();
        
        \DB::table('user_store_permissions')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'store_id' => 3,
                'user_id' => 39,
                'sell' => 1,
                'purchase' => 1,
                'stores_transfer' => 1,
                'stock_adjustment' => 1,
                'sell_return' => 1,
                'created_by' => 7,
                'created_at' => '2024-02-28 08:43:18',
                'updated_at' => '2024-02-28 08:43:18',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'store_id' => 3,
                'user_id' => 38,
                'sell' => 1,
                'purchase' => 1,
                'stores_transfer' => 1,
                'stock_adjustment' => 1,
                'sell_return' => 1,
                'created_by' => 7,
                'created_at' => '2024-02-28 08:43:36',
                'updated_at' => '2024-02-28 08:43:36',
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 4,
                'store_id' => 3,
                'user_id' => 37,
                'sell' => 1,
                'purchase' => 1,
                'stores_transfer' => 1,
                'stock_adjustment' => 1,
                'sell_return' => 1,
                'created_by' => 7,
                'created_at' => '2024-03-01 05:16:20',
                'updated_at' => '2024-03-01 05:16:20',
            ),
            3 => 
            array (
                'id' => 4,
                'business_id' => 4,
                'store_id' => 3,
                'user_id' => 58,
                'sell' => 1,
                'purchase' => 1,
                'stores_transfer' => 1,
                'stock_adjustment' => 1,
                'sell_return' => 1,
                'created_by' => 7,
                'created_at' => '2024-09-12 11:57:21',
                'updated_at' => '2024-09-12 11:57:21',
            ),
        ));
        
        
    }
}