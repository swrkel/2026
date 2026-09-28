<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EditAccountEntriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('edit_account_entries')->delete();
        
        \DB::table('edit_account_entries')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'account_id' => 137,
                'account_transaction_id' => 1826,
                'date_and_time' => '2024-06-18 04:18:55',
                'orignal_amount' => '86456.785000',
                'edited_amount' => '86456.785000',
                'action_type' => 'deleted',
                'created_at' => '2024-06-18 04:18:55',
                'updated_at' => '2024-06-18 04:18:55',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'account_id' => 137,
                'account_transaction_id' => 1863,
                'date_and_time' => '2024-06-18 04:18:55',
                'orignal_amount' => '86456.785000',
                'edited_amount' => '86456.785000',
                'action_type' => 'deleted',
                'created_at' => '2024-06-18 04:18:55',
                'updated_at' => '2024-06-18 04:18:55',
            ),
        ));
        
        
    }
}