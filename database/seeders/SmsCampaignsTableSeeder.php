<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SmsCampaignsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('sms_campaigns')->delete();
        
        
        
    }
}