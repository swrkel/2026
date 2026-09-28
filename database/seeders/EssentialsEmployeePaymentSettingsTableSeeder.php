<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EssentialsEmployeePaymentSettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('essentials_employee_payment_settings')->delete();
        
        \DB::table('essentials_employee_payment_settings')->insert(array (
            0 => 
            array (
                'id' => 1,
                'liability_account_id' => 155,
                'name' => 'Salary',
                'status' => 1,
                'employee_ledger' => 1,
                'datetime_entered' => '2025-05-01 07:30:58',
                'remarks' => NULL,
                'user_id' => 7,
                'business_id' => 4,
                'created_at' => '2025-05-03 03:01:23',
                'updated_at' => '2025-05-03 03:01:23',
            ),
        ));
        
        
    }
}