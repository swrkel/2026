<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AuditsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('audits')->delete();
        
        \DB::table('audits')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_type' => NULL,
                'user_id' => NULL,
                'event' => 'created',
                'auditable_type' => 'App\\User',
                'auditable_id' => 1,
                'old_values' => '[]',
                'new_values' => '{"name":"SYZYGY","email_verified_at":"2024-10-05 05:51:22","email":"vikum12@gmail.com","avatar":"https:\\/\\/s.gravatar.com\\/avatar\\/c605000698652301d96c1992ce948580?s=64&d=mp","password":"$2y$10$zfq0XP2sBiR90qvh0YgemuOi0KfDvizVFvQQ2lQeJkKcix2Osy3oi","id":1}',
                'url' => 'https://helpguide.vim20.xyz/install/admin_account',
                'ip_address' => '61.245.169.253',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36 OPR/113.0.0.0',
                'tags' => NULL,
                'created_at' => '2024-10-05 01:21:22',
                'updated_at' => '2024-10-05 01:21:22',
            ),
            1 => 
            array (
                'id' => 2,
                'user_type' => 'App\\User',
                'user_id' => 1,
                'event' => 'updated',
                'auditable_type' => 'App\\User',
                'auditable_id' => 1,
                'old_values' => '{"last_login_at":null,"last_login_ip":null}',
                'new_values' => '{"last_login_at":"2024-10-05 05:51:48","last_login_ip":"61.245.169.253"}',
                'url' => 'https://helpguide.vim20.xyz/login',
                'ip_address' => '61.245.169.253',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36 OPR/113.0.0.0',
                'tags' => NULL,
                'created_at' => '2024-10-05 01:21:48',
                'updated_at' => '2024-10-05 01:21:48',
            ),
            2 => 
            array (
                'id' => 3,
                'user_type' => 'App\\User',
                'user_id' => 1,
                'event' => 'updated',
                'auditable_type' => 'App\\User',
                'auditable_id' => 1,
                'old_values' => '{"last_login_at":"2024-10-05 05:51:48","last_login_ip":"61.245.169.253"}',
                'new_values' => '{"last_login_at":"2024-10-17 23:56:07","last_login_ip":"112.135.66.99"}',
                'url' => 'https://helpguide.vim20.xyz/login',
                'ip_address' => '112.135.66.99',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36 OPR/113.0.0.0',
                'tags' => NULL,
                'created_at' => '2024-10-17 19:26:07',
                'updated_at' => '2024-10-17 19:26:07',
            ),
            3 => 
            array (
                'id' => 4,
                'user_type' => 'App\\User',
                'user_id' => 1,
                'event' => 'updated',
                'auditable_type' => 'App\\User',
                'auditable_id' => 1,
                'old_values' => '{"last_login_at":"2024-10-17 23:56:07","last_login_ip":"112.135.66.99"}',
                'new_values' => '{"last_login_at":"2024-10-18 10:43:42","last_login_ip":"196.188.245.48"}',
                'url' => 'https://helpguide.vim20.xyz/login',
                'ip_address' => '196.188.245.48',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
                'tags' => NULL,
                'created_at' => '2024-10-18 06:13:43',
                'updated_at' => '2024-10-18 06:13:43',
            ),
            4 => 
            array (
                'id' => 5,
                'user_type' => 'App\\User',
                'user_id' => 1,
                'event' => 'updated',
                'auditable_type' => 'App\\User',
                'auditable_id' => 1,
                'old_values' => '{"last_login_at":"2024-10-18 10:43:42"}',
                'new_values' => '{"last_login_at":"2024-10-18 10:59:22"}',
                'url' => 'https://helpguide.vim20.xyz/login',
                'ip_address' => '196.188.245.48',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
                'tags' => NULL,
                'created_at' => '2024-10-18 06:29:22',
                'updated_at' => '2024-10-18 06:29:22',
            ),
            5 => 
            array (
                'id' => 6,
                'user_type' => 'App\\User',
                'user_id' => 1,
                'event' => 'created',
                'auditable_type' => 'App\\User',
                'auditable_id' => 2,
                'old_values' => '[]',
                'new_values' => '{"name":"ERMIAS","email":"abcd@gmail.com","avatar":"https:\\/\\/s.gravatar.com\\/avatar\\/46040c38d1cbe8ffcd3df6c8ba787951?s=64&d=mp","email_verified_at":"2024-10-18 11:06:42","password":"$2y$10$ScMjosraaTzLf2KstDynUeNrSveS6eLg2E5dGxxMNhP05Tg\\/rAnUe","id":2}',
                'url' => 'https://helpguide.vim20.xyz/dashboard/customers/create',
                'ip_address' => '196.188.245.48',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
                'tags' => NULL,
                'created_at' => '2024-10-18 06:36:42',
                'updated_at' => '2024-10-18 06:36:42',
            ),
            6 => 
            array (
                'id' => 7,
                'user_type' => 'App\\User',
                'user_id' => 1,
                'event' => 'updated',
                'auditable_type' => 'App\\User',
                'auditable_id' => 1,
                'old_values' => '{"last_login_at":"2024-10-18 10:59:22"}',
                'new_values' => '{"last_login_at":"2024-10-18 13:12:28"}',
                'url' => 'https://helpguide.vim20.xyz/login',
                'ip_address' => '196.188.245.48',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
                'tags' => NULL,
                'created_at' => '2024-10-18 08:42:28',
                'updated_at' => '2024-10-18 08:42:28',
            ),
            7 => 
            array (
                'id' => 8,
                'user_type' => 'App\\User',
                'user_id' => 1,
                'event' => 'updated',
                'auditable_type' => 'App\\User',
                'auditable_id' => 1,
                'old_values' => '{"last_login_at":"2024-10-18 13:12:28","last_login_ip":"196.188.245.48"}',
                'new_values' => '{"last_login_at":"2024-10-19 13:43:47","last_login_ip":"197.156.95.254"}',
                'url' => 'https://helpguide.vim20.xyz/login',
                'ip_address' => '197.156.95.254',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
                'tags' => NULL,
                'created_at' => '2024-10-19 09:13:47',
                'updated_at' => '2024-10-19 09:13:47',
            ),
            8 => 
            array (
                'id' => 9,
                'user_type' => 'App\\User',
                'user_id' => 1,
                'event' => 'updated',
                'auditable_type' => 'App\\User',
                'auditable_id' => 1,
                'old_values' => '{"last_login_at":"2024-10-19 13:43:47"}',
                'new_values' => '{"last_login_at":"2024-10-19 13:43:53"}',
                'url' => 'https://helpguide.vim20.xyz/login',
                'ip_address' => '197.156.95.254',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
                'tags' => NULL,
                'created_at' => '2024-10-19 09:13:53',
                'updated_at' => '2024-10-19 09:13:53',
            ),
            9 => 
            array (
                'id' => 10,
                'user_type' => 'App\\User',
                'user_id' => 1,
                'event' => 'updated',
                'auditable_type' => 'App\\User',
                'auditable_id' => 1,
                'old_values' => '{"last_login_at":"2024-10-19 13:43:53"}',
                'new_values' => '{"last_login_at":"2024-10-19 13:44:07"}',
                'url' => 'https://helpguide.vim20.xyz/login',
                'ip_address' => '197.156.95.254',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
                'tags' => NULL,
                'created_at' => '2024-10-19 09:14:07',
                'updated_at' => '2024-10-19 09:14:07',
            ),
            10 => 
            array (
                'id' => 11,
                'user_type' => 'App\\User',
                'user_id' => 1,
                'event' => 'updated',
                'auditable_type' => 'App\\User',
                'auditable_id' => 1,
                'old_values' => '{"last_login_at":"2024-10-19 13:44:07","last_login_ip":"197.156.95.254"}',
                'new_values' => '{"last_login_at":"2024-10-22 17:03:19","last_login_ip":"102.218.50.116"}',
                'url' => 'https://helpguide.vim20.xyz/login',
                'ip_address' => '102.218.50.116',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
                'tags' => NULL,
                'created_at' => '2024-10-22 12:33:19',
                'updated_at' => '2024-10-22 12:33:19',
            ),
            11 => 
            array (
                'id' => 12,
                'user_type' => 'App\\User',
                'user_id' => 1,
                'event' => 'updated',
                'auditable_type' => 'App\\User',
                'auditable_id' => 1,
                'old_values' => '{"last_login_at":"2024-10-22 17:03:19"}',
                'new_values' => '{"last_login_at":"2024-10-22 17:09:42"}',
                'url' => 'https://helpguide.vim20.xyz/login',
                'ip_address' => '102.218.50.116',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
                'tags' => NULL,
                'created_at' => '2024-10-22 12:39:43',
                'updated_at' => '2024-10-22 12:39:43',
            ),
            12 => 
            array (
                'id' => 13,
                'user_type' => 'App\\User',
                'user_id' => 1,
                'event' => 'updated',
                'auditable_type' => 'App\\User',
                'auditable_id' => 1,
                'old_values' => '{"last_login_at":"2024-10-22 17:09:42","last_login_ip":"102.218.50.116"}',
                'new_values' => '{"last_login_at":"2024-10-23 10:06:04","last_login_ip":"41.209.57.187"}',
                'url' => 'https://helpguide.vim20.xyz/login',
                'ip_address' => '41.209.57.187',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
                'tags' => NULL,
                'created_at' => '2024-10-23 05:36:04',
                'updated_at' => '2024-10-23 05:36:04',
            ),
            13 => 
            array (
                'id' => 14,
                'user_type' => 'App\\User',
                'user_id' => 1,
                'event' => 'updated',
                'auditable_type' => 'App\\User',
                'auditable_id' => 1,
                'old_values' => '{"last_login_at":"2024-10-23 10:06:04"}',
                'new_values' => '{"last_login_at":"2024-10-23 10:47:47"}',
                'url' => 'https://helpguide.vim20.xyz/login',
                'ip_address' => '41.209.57.187',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
                'tags' => NULL,
                'created_at' => '2024-10-23 06:17:47',
                'updated_at' => '2024-10-23 06:17:47',
            ),
            14 => 
            array (
                'id' => 15,
                'user_type' => 'App\\User',
                'user_id' => 1,
                'event' => 'updated',
                'auditable_type' => 'App\\User',
                'auditable_id' => 1,
                'old_values' => '{"last_login_at":"2024-10-24 09:05:13"}',
                'new_values' => '{"last_login_at":"2024-10-24 09:06:08"}',
                'url' => 'http://127.0.0.1:8000/login',
                'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
                'tags' => NULL,
                'created_at' => '2024-10-24 07:06:08',
                'updated_at' => '2024-10-24 07:06:08',
            ),
        ));
        
        
    }
}