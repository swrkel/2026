<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EssentialsEmployeeAdvancesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('essentials_employee_advances')->delete();
        
        \DB::table('essentials_employee_advances')->insert(array (
            0 => 
            array (
                'id' => 1,
                'employee_id' => 1,
                'amount' => 6000.0,
                'amount_paid' => 6000.0,
                'payment_status' => 1,
                'employee_ledger_applicable' => 1,
                'payment_datetime' => NULL,
                'check_no' => NULL,
                'reference_no' => NULL,
                'remarks' => NULL,
                'salary_period_start' => '2025-05-01',
                'salary_period_end' => '2025-05-31',
                'created_at' => '2025-05-03 03:01:52',
                'updated_at' => '2025-05-03 03:01:52',
                'datetime_entered' => '2025-05-03 00:00:00',
                'payment_type_id' => 1,
                'account_id' => 153,
            ),
            1 => 
            array (
                'id' => 2,
                'employee_id' => 1,
                'amount' => 222.0,
                'amount_paid' => 222.0,
                'payment_status' => 1,
                'employee_ledger_applicable' => 1,
                'payment_datetime' => NULL,
                'check_no' => NULL,
                'reference_no' => NULL,
                'remarks' => NULL,
                'salary_period_start' => '2025-05-01',
                'salary_period_end' => '2025-05-31',
                'created_at' => '2025-05-03 05:13:52',
                'updated_at' => '2025-05-03 05:13:52',
                'datetime_entered' => '2025-05-03 00:00:00',
                'payment_type_id' => 1,
                'account_id' => 154,
            ),
            2 => 
            array (
                'id' => 3,
                'employee_id' => 1,
                'amount' => 3333.0,
                'amount_paid' => 3333.0,
                'payment_status' => 1,
                'employee_ledger_applicable' => 1,
                'payment_datetime' => NULL,
                'check_no' => NULL,
                'reference_no' => NULL,
                'remarks' => NULL,
                'salary_period_start' => '2025-05-01',
                'salary_period_end' => '2025-05-31',
                'created_at' => '2025-05-03 09:47:45',
                'updated_at' => '2025-05-03 09:47:45',
                'datetime_entered' => '2025-05-03 00:00:00',
                'payment_type_id' => 1,
                'account_id' => 157,
            ),
            3 => 
            array (
                'id' => 4,
                'employee_id' => 1,
                'amount' => 1212.0,
                'amount_paid' => 1212.0,
                'payment_status' => 1,
                'employee_ledger_applicable' => 1,
                'payment_datetime' => NULL,
                'check_no' => NULL,
                'reference_no' => NULL,
                'remarks' => NULL,
                'salary_period_start' => '2025-05-01',
                'salary_period_end' => '2025-05-31',
                'created_at' => '2025-05-03 09:56:58',
                'updated_at' => '2025-05-03 09:56:58',
                'datetime_entered' => '2025-05-03 00:00:00',
                'payment_type_id' => 1,
                'account_id' => 153,
            ),
            4 => 
            array (
                'id' => 5,
                'employee_id' => 1,
                'amount' => 10.0,
                'amount_paid' => 10.0,
                'payment_status' => 1,
                'employee_ledger_applicable' => 1,
                'payment_datetime' => NULL,
                'check_no' => NULL,
                'reference_no' => NULL,
                'remarks' => NULL,
                'salary_period_start' => '2025-05-01',
                'salary_period_end' => '2025-05-31',
                'created_at' => '2025-05-10 04:46:11',
                'updated_at' => '2025-05-10 04:46:11',
                'datetime_entered' => '2025-05-10 00:00:00',
                'payment_type_id' => 1,
                'account_id' => 157,
            ),
        ));
        
        
    }
}