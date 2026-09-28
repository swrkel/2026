<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WorkingDaysTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('working_days')->delete();
        
        \DB::table('working_days')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 1,
                'days' => 'Saturday',
                'flag' => 0,
                'created_at' => '2023-03-01 09:36:58',
                'updated_at' => '2023-03-01 09:36:58',
                'is_default' => 0,
                'is_superadmin_default' => 1,
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 1,
                'days' => 'Sunday',
                'flag' => 0,
                'created_at' => '2023-03-01 09:36:58',
                'updated_at' => '2023-03-01 09:36:58',
                'is_default' => 0,
                'is_superadmin_default' => 1,
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 1,
                'days' => 'Monday',
                'flag' => 0,
                'created_at' => '2023-03-01 09:36:58',
                'updated_at' => '2023-03-01 09:36:58',
                'is_default' => 0,
                'is_superadmin_default' => 1,
            ),
            3 => 
            array (
                'id' => 4,
                'business_id' => 1,
                'days' => 'Tuesday',
                'flag' => 0,
                'created_at' => '2023-03-01 09:36:58',
                'updated_at' => '2023-03-01 09:36:58',
                'is_default' => 0,
                'is_superadmin_default' => 1,
            ),
            4 => 
            array (
                'id' => 5,
                'business_id' => 1,
                'days' => 'Wednesday',
                'flag' => 0,
                'created_at' => '2023-03-01 09:36:58',
                'updated_at' => '2023-03-01 09:36:58',
                'is_default' => 0,
                'is_superadmin_default' => 1,
            ),
            5 => 
            array (
                'id' => 6,
                'business_id' => 1,
                'days' => 'Thursday',
                'flag' => 0,
                'created_at' => '2023-03-01 09:36:58',
                'updated_at' => '2023-03-01 09:36:58',
                'is_default' => 0,
                'is_superadmin_default' => 1,
            ),
            6 => 
            array (
                'id' => 7,
                'business_id' => 1,
                'days' => 'Friday',
                'flag' => 0,
                'created_at' => '2023-03-01 09:36:58',
                'updated_at' => '2023-03-01 09:36:58',
                'is_default' => 0,
                'is_superadmin_default' => 1,
            ),
        ));
        
        
    }
}