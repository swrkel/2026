<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VatCustomerStatementDetailsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('vat_customer_statement_details')->delete();

    }
}