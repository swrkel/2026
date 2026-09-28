<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ChequerBankAccountsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('chequer_bank_accounts')->delete();
        
        
        
    }
}