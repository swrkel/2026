<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('settings')->delete();
        
        \DB::table('settings')->insert(array (
            0 => 
            array (
                'id' => 1,
                'google_key' => NULL,
                'google_analytics_id' => 'UA-554155-3',
                'site_name' => 'EzyPetRo International',
                'site_logo' => '/backend/img/IMG-1720892044.png',
                'favicon' => '/backend/img/IMG-1726747753.png',
                'seo_meta_description' => 'Manage Your Gas / Filling Stations in a Smart Way',
                'seo_keywords' => 'business, filling stations, digital, filling, gas, petrol, sheds, diesel, lubricants, save time and cost',
                'seo_image' => '/backend/img/IMG-1661927299.png',
                'tawk_chat_bot_key' => NULL,
                'name' => 'SYZYGY vCards',
                'address' => 'support@syzygyvcard.com',
                'driver' => 'smtp',
                'host' => 'smtp.mailtrap.io',
                'port' => 2525,
                'encryption' => 'tls',
                'username' => 'support@syzygyvcard.com',
                'password' => 'ipY2QenCUW5',
                'status' => '1',
                'created_at' => '2021-10-09 10:32:44',
                'updated_at' => '2024-09-19 13:09:13',
                'quantity' => '0.00',
            ),
        ));
        
        
    }
}