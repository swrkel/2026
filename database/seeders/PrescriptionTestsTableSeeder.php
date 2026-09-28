<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PrescriptionTestsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('prescription_tests')->delete();
        
        
        
    }
}