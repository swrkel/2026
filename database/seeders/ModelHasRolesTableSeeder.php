<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ModelHasRolesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('model_has_roles')->delete();
        
        \DB::table('model_has_roles')->insert(array (
            0 => 
            array (
                'role_id' => 1,
                'model_type' => 'App\\User',
                'model_id' => 1,
            ),
            1 => 
            array (
                'role_id' => 2,
                'model_type' => 'App\\User',
                'model_id' => 2,
            ),
            2 => 
            array (
                'role_id' => 5,
                'model_type' => 'App\\User',
                'model_id' => 3,
            ),
            3 => 
            array (
                'role_id' => 8,
                'model_type' => 'App\\User',
                'model_id' => 7,
            ),
            4 => 
            array (
                'role_id' => 8,
                'model_type' => 'App\\User',
                'model_id' => 38,
            ),
            5 => 
            array (
                'role_id' => 8,
                'model_type' => 'App\\User',
                'model_id' => 42,
            ),
            6 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 8,
            ),
            7 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 9,
            ),
            8 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 10,
            ),
            9 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 11,
            ),
            10 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 12,
            ),
            11 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 13,
            ),
            12 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 14,
            ),
            13 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 15,
            ),
            14 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 16,
            ),
            15 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 17,
            ),
            16 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 40,
            ),
            17 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 41,
            ),
            18 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 44,
            ),
            19 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 49,
            ),
            20 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 50,
            ),
            21 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 52,
            ),
            22 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 54,
            ),
            23 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 55,
            ),
            24 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 56,
            ),
            25 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 62,
            ),
            26 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 65,
            ),
            27 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 69,
            ),
            28 => 
            array (
                'role_id' => 10,
                'model_type' => 'App\\User',
                'model_id' => 77,
            ),
            29 => 
            array (
                'role_id' => 11,
                'model_type' => 'App\\User',
                'model_id' => 37,
            ),
            30 => 
            array (
                'role_id' => 11,
                'model_type' => 'App\\User',
                'model_id' => 58,
            ),
            31 => 
            array (
                'role_id' => 12,
                'model_type' => 'App\\User',
                'model_id' => 39,
            ),
        ));
        
        
    }
}