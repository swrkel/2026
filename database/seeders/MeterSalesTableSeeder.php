<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MeterSalesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('meter_sales')->delete();

    }
}