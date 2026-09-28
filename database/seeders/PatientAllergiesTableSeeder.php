<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PatientAllergiesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('patient_allergies')->delete();
        
        
        
    }
}