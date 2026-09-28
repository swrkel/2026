<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PrescriptionMedicinesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('prescription_medicines')->delete();
        
        
        
    }
}