<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DocManagementForwardWithsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('doc_management_forward_withs')->delete();
        
        \DB::table('doc_management_forward_withs')->insert(array (
            0 => 
            array (
                'id' => 1,
                'forwarded_with' => 'Approved ',
                'user' => '29copy',
                'created_at' => '2024-01-29 04:54:47',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            1 => 
            array (
                'id' => 2,
                'forwarded_with' => 'Prepared',
                'user' => '29copy',
                'created_at' => '2024-02-04 18:15:39',
                'updated_at' => '2024-02-04 18:15:39',
            ),
            2 => 
            array (
                'id' => 3,
                'forwarded_with' => 'Check again and send me',
                'user' => '29copy',
                'created_at' => '2024-02-06 18:42:31',
                'updated_at' => '2024-02-06 18:42:31',
            ),
            3 => 
            array (
                'id' => 4,
                'forwarded_with' => 'For your information',
                'user' => '29copy',
                'created_at' => '2024-02-06 18:42:44',
                'updated_at' => '2024-02-06 18:42:44',
            ),
            4 => 
            array (
                'id' => 5,
                'forwarded_with' => 'Further information Required',
                'user' => '29copy',
                'created_at' => '2024-02-06 18:42:56',
                'updated_at' => '2024-02-06 18:42:56',
            ),
        ));
        
        
    }
}