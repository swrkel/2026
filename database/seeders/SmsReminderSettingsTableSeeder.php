<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SmsReminderSettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('sms_reminder_settings')->delete();
        
        
        
    }
}