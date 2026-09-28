<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AirlineClassesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('airline_classes')->delete();
        
        \DB::table('airline_classes')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Economy',
                'created_at' => '2024-09-18 04:07:19',
                'updated_at' => '2024-09-18 04:07:19',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Business',
                'created_at' => '2024-09-18 04:07:19',
                'updated_at' => '2024-09-18 04:07:19',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'First Class',
                'created_at' => '2024-09-18 04:07:19',
                'updated_at' => '2024-09-18 04:07:19',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Private',
                'created_at' => '2024-09-18 07:16:29',
                'updated_at' => '2024-09-18 07:16:29',
            ),
        ));
        
        
    }
}