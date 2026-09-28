<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VerifybackupTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('verifybackup')->delete();
        
        \DB::table('verifybackup')->insert(array (
            0 => 
            array (
                'id' => 1,
                'verify_status' => 'backup',
            ),
        ));
        
        
    }
}