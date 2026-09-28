<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PatientPaymentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('patient_payments')->delete();
        
        
        
    }
}