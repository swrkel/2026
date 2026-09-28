<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SettlementCreditSalePaymentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('settlement_credit_sale_payments')->delete();

    }
}