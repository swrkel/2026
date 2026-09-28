<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WorkShiftsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('work_shifts')->delete();
        
        \DB::table('work_shifts')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'shift_name' => '24 Hours',
                'shift_form' => '08:00 am',
                'shift_to' => '08:00 am',
                'created_at' => '2024-02-26 15:49:01',
                'updated_at' => '2024-02-26 15:49:01',
                'is_default' => 0,
                'is_superadmin_default' => 0,
            ),
        ));
        
        
    }
}