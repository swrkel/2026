<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PrioritiesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('priorities')->delete();
        
        \DB::table('priorities')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'name' => 'Low',
                'color' => '',
                'date' => '2024-02-26',
                'added_by' => 1,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'name' => 'Medium',
                'color' => '',
                'date' => '2024-02-26',
                'added_by' => 1,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 4,
                'name' => 'High',
                'color' => '',
                'date' => '2024-02-26',
                'added_by' => 1,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            3 => 
            array (
                'id' => 4,
                'business_id' => 4,
                'name' => 'Urgent',
                'color' => '',
                'date' => '2024-02-26',
                'added_by' => 1,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            4 => 
            array (
                'id' => 5,
                'business_id' => 4,
                'name' => 'Critical',
                'color' => '',
                'date' => '2024-02-26',
                'added_by' => 1,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
        ));
        
        
    }
}