<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HelpguideModelHasRolesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('helpguide_model_has_roles')->delete();
        
        \DB::table('helpguide_model_has_roles')->insert(array (
            0 => 
            array (
                'role_id' => 1,
                'model_type' => 'App\\User',
                'model_id' => 1,
            ),
            1 => 
            array (
                'role_id' => 5,
                'model_type' => 'App\\User',
                'model_id' => 2,
            ),
        ));
        
        
    }
}