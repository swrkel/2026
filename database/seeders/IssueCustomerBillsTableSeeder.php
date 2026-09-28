<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class IssueCustomerBillsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('issue_customer_bills')->delete();
        
        
        
    }
}