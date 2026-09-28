<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ArticlesettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('articlesettings')->delete();
        
        \DB::table('articlesettings')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'app_logo',
                'val' => 'https://vimi50.site/storage/tenantcoolishadi/app/public/resources/UYC9KLpiIU0I0G8GxFwwSPWdDouoAqs7yPkOmI8O.png',
                'type' => 'file',
                'created_at' => '2024-10-05 01:20:35',
                'updated_at' => '2024-10-05 01:25:25',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'app_name',
                'val' => 'Help Guide',
                'type' => 'string',
                'created_at' => '2024-10-05 01:20:35',
                'updated_at' => '2024-10-05 01:24:30',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'default_lang',
                'val' => 'en',
                'type' => 'string',
                'created_at' => '2024-10-05 01:20:35',
                'updated_at' => '2024-10-05 01:20:35',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'favicon',
                'val' => 'https://helpguide.vim20.xyz/storage/resources/XjH1O6xtweJkS2tdrLeuNkAN2Bg3PQ0GittHvaTS.png',
                'type' => 'file',
                'created_at' => '2024-10-05 01:20:35',
                'updated_at' => '2024-10-05 01:25:30',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'date_format',
                'val' => 'j, n, Y',
                'type' => 'string',
                'created_at' => '2024-10-05 01:20:35',
                'updated_at' => '2024-10-05 01:41:50',
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'mail_channel',
                'val' => 'sendmail',
                'type' => 'string',
                'created_at' => '2024-10-05 01:20:35',
                'updated_at' => '2024-10-05 01:20:35',
            ),
            6 => 
            array (
                'id' => 7,
                'name' => 'user_can_register',
                'val' => '0',
                'type' => 'integer',
                'created_at' => '2024-10-05 01:20:35',
                'updated_at' => '2024-10-05 01:41:43',
            ),
            7 => 
            array (
                'id' => 8,
                'name' => 'mail_from_address',
                'val' => 'support@vim20.xyz',
                'type' => 'string',
                'created_at' => '2024-10-05 01:20:35',
                'updated_at' => '2024-10-05 01:43:36',
            ),
            8 => 
            array (
                'id' => 9,
                'name' => 'mail_from_name',
                'val' => 'SYZYGY',
                'type' => 'string',
                'created_at' => '2024-10-05 01:20:35',
                'updated_at' => '2024-10-05 01:44:00',
            ),
            9 => 
            array (
                'id' => 10,
                'name' => 'timezone',
                'val' => 'UTC',
                'type' => 'string',
                'created_at' => '2024-10-05 01:20:35',
                'updated_at' => '2024-10-05 01:20:35',
            ),
            10 => 
            array (
                'id' => 11,
                'name' => 'verify_email',
                'val' => '0',
                'type' => 'boolean',
                'created_at' => '2024-10-05 01:20:35',
                'updated_at' => '2024-10-05 01:41:43',
            ),
        ));
        
        
    }
}