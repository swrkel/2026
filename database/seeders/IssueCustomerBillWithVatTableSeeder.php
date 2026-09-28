<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class IssueCustomerBillWithVatTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('issue_customer_bill_with_vat')->delete();
        
        
        
    }
}