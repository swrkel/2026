<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HrmDesignationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('hrm_designations')->delete();
        
        \DB::table('hrm_designations')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'department_id' => 1,
                'name' => 'Designation 1',
                'description' => '1',
                'created_by' => 7,
                'created_at' => '2025-05-03 02:59:27',
                'updated_at' => '2025-05-03 02:59:27',
            ),
        ));
        
        
    }
}