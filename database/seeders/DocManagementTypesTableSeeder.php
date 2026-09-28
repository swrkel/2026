<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DocManagementTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('doc_management_types')->delete();
        
        \DB::table('doc_management_types')->insert(array (
            0 => 
            array (
                'id' => 1,
                'type' => 'Purchase Related',
                'user' => '29copy',
                'created_at' => '2024-01-28 17:47:33',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            1 => 
            array (
                'id' => 2,
                'type' => 'Sales Tpe',
                'user' => '29copy',
                'created_at' => '2024-01-30 09:03:26',
                'updated_at' => '2024-01-30 13:33:26',
            ),
            2 => 
            array (
                'id' => 3,
                'type' => 'Sales Return Related',
                'user' => '29copy',
                'created_at' => '2024-01-30 09:06:11',
                'updated_at' => '2024-01-30 13:36:11',
            ),
            3 => 
            array (
                'id' => 4,
                'type' => 'Commerce',
                'user' => '29copy',
                'created_at' => '2024-02-04 17:33:05',
                'updated_at' => '2024-02-04 22:03:05',
            ),
        ));
        
        
    }
}