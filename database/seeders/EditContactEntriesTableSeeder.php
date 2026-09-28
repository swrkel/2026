<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EditContactEntriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('edit_contact_entries')->delete();
        
        \DB::table('edit_contact_entries')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'account_id' => NULL,
                'contact_id' => 49,
                'contact_ledger_id' => 6202,
                'date_and_time' => '2024-06-18 04:11:26',
                'orignal_amount' => '92099.000000',
                'edited_amount' => '92099.000000',
                'action_type' => 'deleted',
                'created_at' => '2024-06-18 04:11:26',
                'updated_at' => '2024-06-18 04:11:26',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'account_id' => NULL,
                'contact_id' => 49,
                'contact_ledger_id' => 6201,
                'date_and_time' => '2024-06-18 04:20:22',
                'orignal_amount' => '92099.000000',
                'edited_amount' => '92099.000000',
                'action_type' => 'deleted',
                'created_at' => '2024-06-18 04:20:22',
                'updated_at' => '2024-06-18 04:20:22',
            ),
        ));
        
        
    }
}