<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ModelHasPermissionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('model_has_permissions')->delete();
        
        \DB::table('model_has_permissions')->insert(array (
            0 => 
            array (
                'permission_id' => 74,
                'model_type' => 'App\\User',
                'model_id' => 37,
            ),
            1 => 
            array (
                'permission_id' => 74,
                'model_type' => 'App\\User',
                'model_id' => 38,
            ),
            2 => 
            array (
                'permission_id' => 74,
                'model_type' => 'App\\User',
                'model_id' => 39,
            ),
            3 => 
            array (
                'permission_id' => 74,
                'model_type' => 'App\\User',
                'model_id' => 42,
            ),
            4 => 
            array (
                'permission_id' => 74,
                'model_type' => 'App\\User',
                'model_id' => 58,
            ),
        ));
        
        
    }
}