<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatPurchasesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vat_purchases')->delete();
        
        
        
    }
}