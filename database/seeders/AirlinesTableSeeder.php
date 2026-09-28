<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AirlinesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('airlines')->delete();
        
        \DB::table('airlines')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_id' => 3,
                'airline' => 'Qatar Airways',
                'created_at' => '2023-09-30 01:27:41',
                'updated_at' => '2023-09-30 01:27:41',
            ),
            1 => 
            array (
                'id' => 2,
                'user_id' => 3,
                'airline' => 'Sri Lankan air Lines',
                'created_at' => '2023-12-17 09:24:21',
                'updated_at' => '2023-12-17 09:24:21',
            ),
        ));
        
        
    }
}