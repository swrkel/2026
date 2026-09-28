<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserSettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('user_settings')->delete();
        
        \DB::table('user_settings')->insert(array (
            0 => 
            array (
                'id' => 3,
                'user_id' => 7,
                're_captcha_enabled' => NULL,
                're_captcha_enabled_date' => NULL,
                'verification_done' => 0,
                'opt_verification_enabled' => NULL,
                'verification_attempt_count' => 0,
                'opt_verification_enabled_date' => NULL,
                'created_at' => '2025-04-01 16:27:32',
                'updated_at' => '2025-05-27 04:23:38',
            ),
        ));
        
        
    }
}