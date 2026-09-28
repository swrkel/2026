<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TransactionPaymentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('transaction_payments')->delete();

    }
}