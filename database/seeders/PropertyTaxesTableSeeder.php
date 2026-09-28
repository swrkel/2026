<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PropertyTaxesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('property_taxes')->delete();
        
        
        
    }
}