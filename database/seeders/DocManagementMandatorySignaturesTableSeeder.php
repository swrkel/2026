<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DocManagementMandatorySignaturesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('doc_management_mandatory_signatures')->delete();
        
        \DB::table('doc_management_mandatory_signatures')->insert(array (
            0 => 
            array (
                'id' => 1,
                'no_of_mandatory' => 1,
                'signature_level' => 'Prepared by',
                'user' => '20copy',
                'created_at' => '2024-01-30 07:49:11',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            1 => 
            array (
                'id' => 2,
                'no_of_mandatory' => 2,
                'signature_level' => 'GM',
                'user' => '29copy',
                'created_at' => '2024-02-04 18:32:30',
                'updated_at' => '2024-02-04 18:32:30',
            ),
        ));
        
        
    }
}