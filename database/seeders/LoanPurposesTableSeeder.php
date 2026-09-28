<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoanPurposesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('loan_purposes')->delete();
        
        \DB::table('loan_purposes')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 3,
                'name' => 'Invest',
            ),
        ));
        
        
    }
}