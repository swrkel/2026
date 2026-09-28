<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatStatementPrefixesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vat_statement_prefixes')->delete();
        
        \DB::table('vat_statement_prefixes')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'prefix' => 'PB/CS',
                'starting_no' => 20,
                'created_by' => 7,
                'created_at' => '2024-04-03 16:24:43',
                'updated_at' => '2024-04-03 16:24:43',
            ),
        ));
        
        
    }
}