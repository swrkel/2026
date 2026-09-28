<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BusinessReportsConfigurationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('business_reports_configurations')->delete();
        
        \DB::table('business_reports_configurations')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 3,
                'name' => 'customer_statement_report',
                'configurations' => '{"date":"1","location":"0","invoice_no":"1","route":"0","vehicle":"0","customer_reference":"1","customer_po":"1","voucher_date":"0","product":"1","qty":"1","unit_price":"1","invoice_amount":"1","due_amount":"1"}',
                'created_at' => '2023-12-01 08:49:06',
                'updated_at' => '2023-12-01 08:49:06',
            ),
        ));
        
        
    }
}