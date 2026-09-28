<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatPaymentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vat_payments')->delete();
        
        
        
    }
}