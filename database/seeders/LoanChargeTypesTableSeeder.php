<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoanChargeTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('loan_charge_types')->delete();
        
        \DB::table('loan_charge_types')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Disbursement',
                'translated_name' => 'Disbursement',
                'active' => 1,
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Specified Due Date',
                'translated_name' => 'Specified Due Date',
                'active' => 1,
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Installment Fees',
                'translated_name' => 'Installment Fees',
                'active' => 1,
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Overdue Installment Fee',
                'translated_name' => 'Overdue Installment Fee',
                'active' => 1,
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'Disbursement - Paid With Repayment',
                'translated_name' => 'Disbursement - Paid With Repayment',
                'active' => 1,
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'Loan Rescheduling Fee',
                'translated_name' => 'Loan Rescheduling Fee',
                'active' => 1,
            ),
            6 => 
            array (
                'id' => 7,
                'name' => 'Overdue On Loan Maturity',
                'translated_name' => 'Overdue On Loan Maturity',
                'active' => 1,
            ),
            7 => 
            array (
                'id' => 8,
                'name' => 'Last installment fee',
                'translated_name' => 'Last installment fee',
                'active' => 1,
            ),
        ));
        
        
    }
}