<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoanStatusesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('loan_statuses')->delete();
        
        \DB::table('loan_statuses')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 3,
                'parent_status' => 'closed',
                'name' => 'Pending',
                'active' => 1,
                'created_at' => '2023-05-26 20:22:29',
                'updated_at' => '2023-05-26 20:22:29',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 3,
                'parent_status' => 'closed',
                'name' => 'Approved',
                'active' => 1,
                'created_at' => '2023-05-26 20:22:48',
                'updated_at' => '2023-05-26 20:22:48',
            ),
        ));
        
        
    }
}