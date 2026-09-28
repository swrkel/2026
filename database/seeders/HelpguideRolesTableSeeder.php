<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HelpguideRolesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('helpguide_roles')->delete();
        
        \DB::table('helpguide_roles')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'super_admin',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:32',
                'updated_at' => '2024-10-05 01:20:32',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'admin',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:33',
                'updated_at' => '2024-10-05 01:20:33',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'agent',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:34',
                'updated_at' => '2024-10-05 01:20:34',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'non-restricted_agent',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:34',
                'updated_at' => '2024-10-05 01:20:34',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'customer',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:34',
                'updated_at' => '2024-10-05 01:20:34',
            ),
        ));
        
        
    }
}