<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PatientDoctorsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('patient_doctors')->delete();
        
        
        
    }
}