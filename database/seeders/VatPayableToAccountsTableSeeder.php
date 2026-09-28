<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatPayableToAccountsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('vat_payable_to_accounts')->delete();
        
        \DB::table('vat_payable_to_accounts')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'account_id' => 149,
                'type' => 'vat_payable_account',
                'amount' => '0.000',
                'transaction_id' => 461,
                'note' => NULL,
                'created_by' => 7,
                'created_at' => '2024-03-05 05:17:09',
                'updated_at' => '2024-03-05 05:17:09',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'account_id' => 217,
                'type' => 'vat_receivable_account',
                'amount' => '0.000',
                'transaction_id' => 462,
                'note' => NULL,
                'created_by' => 7,
                'created_at' => '2024-03-05 05:19:13',
                'updated_at' => '2024-03-05 05:19:13',
            ),
        ));
        
        
    }
}