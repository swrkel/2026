<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatBankDetailsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vat_bank_details')->delete();
        
        
        
    }
}