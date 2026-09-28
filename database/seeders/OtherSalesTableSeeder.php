<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OtherSalesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('other_sales')->delete();

    }
}