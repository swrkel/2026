<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HmsExtrasTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('hms_extras')->delete();
        
        \DB::table('hms_extras')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Extra 1',
                'price' => '150.0000',
                'price_per' => 'per_day',
                'business_id' => 4,
                'created_by' => 7,
                'is_active' => 1,
                'created_at' => '2025-04-17 13:15:19',
                'updated_at' => '2025-04-17 13:17:31',
            ),
        ));
        
        
    }
}