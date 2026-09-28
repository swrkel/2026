<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoanChargeOptionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('loan_charge_options')->delete();
        
        \DB::table('loan_charge_options')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Flat',
                'translated_name' => 'Flat',
                'active' => 1,
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Principal due on installment',
                'translated_name' => 'Principal due on installment',
                'active' => 1,
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Principal + Interest due on installment',
                'translated_name' => 'Principal + Interest due on installment',
                'active' => 1,
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Interest due on installment',
                'translated_name' => 'Interest due on installment',
                'active' => 1,
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'Total Outstanding Loan Principal',
                'translated_name' => 'Total Outstanding Loan Principal',
                'active' => 1,
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'Percentage of Original Loan Principal per Installment',
                'translated_name' => 'Percentage of Original Loan Principal per Installment',
                'active' => 1,
            ),
            6 => 
            array (
                'id' => 7,
                'name' => 'Original Loan Principal',
                'translated_name' => 'Original Loan Principal',
                'active' => 1,
            ),
        ));
        
        
    }
}