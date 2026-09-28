<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DefaultTaxRatesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('default_tax_rates')->delete();
        
        \DB::table('default_tax_rates')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'VAT',
                'amount' => 18.0,
                'is_tax_group' => 0,
                'created_by' => 1,
                'deleted_at' => NULL,
                'created_at' => '2024-02-10 09:43:23',
                'updated_at' => '2024-02-10 09:43:23',
                'for_tax_group' => 0,
            ),
        ));
        
        
    }
}