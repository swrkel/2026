<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InstallmentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('installments')->delete();
        
        
        
    }
}