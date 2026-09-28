<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SmsApiClientsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('sms_api_clients')->delete();
        
        
        
    }
}