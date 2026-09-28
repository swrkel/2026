<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DocManagementPurposesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('doc_management_purposes')->delete();
        
        \DB::table('doc_management_purposes')->insert(array (
            0 => 
            array (
                'id' => 1,
                'purpose_type' => 'For approval',
                'user' => '20Copy',
                'created_at' => '2024-01-30 07:23:43',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            1 => 
            array (
                'id' => 2,
                'purpose_type' => 'Deligation',
                'user' => '29copy',
                'created_at' => '2024-02-04 17:59:52',
                'updated_at' => '2024-02-04 17:59:52',
            ),
        ));
        
        
    }
}