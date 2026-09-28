<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CustomerSmsSettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('customer_sms_settings')->delete();
        
        
        
    }
}