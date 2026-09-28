<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TaxRatesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('tax_rates')->delete();
        
        \DB::table('tax_rates')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 1,
                'name' => 'VAT',
                'amount' => 18.0,
                'is_tax_group' => 0,
                'created_by' => 1,
                'deleted_at' => NULL,
                'created_at' => '2024-02-26 15:05:21',
                'updated_at' => '2024-02-26 15:05:21',
                'for_tax_group' => 0,
                'default_tax_id' => 1,
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'name' => 'VAT',
                'amount' => 18.0,
                'is_tax_group' => 0,
                'created_by' => 1,
                'deleted_at' => NULL,
                'created_at' => '2024-02-26 15:40:08',
                'updated_at' => '2024-02-26 15:40:08',
                'for_tax_group' => 0,
                'default_tax_id' => 1,
            ),
        ));
        
        
    }
}