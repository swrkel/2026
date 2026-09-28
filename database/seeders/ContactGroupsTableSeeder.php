<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ContactGroupsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('contact_groups')->delete();
        
        \DB::table('contact_groups')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'type' => 'customer',
                'name' => 'Own Company',
                'amount' => 0.0,
                'supplier_group_id' => NULL,
                'maximum_discount' => NULL,
                'last_maximum_discount' => NULL,
                'created_by' => 0,
                'created_at' => '2024-02-26 15:39:48',
                'updated_at' => '2024-02-26 15:39:48',
                'account_type_id' => NULL,
                'interest_account_id' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'type' => 'supplier',
                'name' => 'Own Company',
                'amount' => 0.0,
                'supplier_group_id' => NULL,
                'maximum_discount' => NULL,
                'last_maximum_discount' => NULL,
                'created_by' => 0,
                'created_at' => '2024-02-26 15:39:48',
                'updated_at' => '2024-02-26 15:39:48',
                'account_type_id' => NULL,
                'interest_account_id' => NULL,
            ),
        ));
        
        
    }
}