<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SettlementsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('settlements')->delete();

    }
}