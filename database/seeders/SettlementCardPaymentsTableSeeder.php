<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SettlementCardPaymentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('settlement_card_payments')->delete();

    }
}