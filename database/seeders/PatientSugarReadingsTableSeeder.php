<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PatientSugarReadingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('patient_sugar_readings')->delete();
        
        
        
    }
}