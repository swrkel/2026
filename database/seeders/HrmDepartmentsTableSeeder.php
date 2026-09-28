<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HrmDepartmentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('hrm_departments')->delete();
        
        \DB::table('hrm_departments')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'name' => 'Department 1',
                'short_code' => '1',
                'description' => '1',
                'created_by' => 7,
                'created_at' => '2025-05-03 02:59:05',
                'updated_at' => '2025-05-03 02:59:05',
            ),
        ));
        
        
    }
}