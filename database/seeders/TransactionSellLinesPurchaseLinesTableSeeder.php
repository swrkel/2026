<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TransactionSellLinesPurchaseLinesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('transaction_sell_lines_purchase_lines')->delete();

    }
}