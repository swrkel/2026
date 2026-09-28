<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HelpguidePermissionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('helpguide_permissions')->delete();
        
        \DB::table('helpguide_permissions')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'create_ticket',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:27',
                'updated_at' => '2024-10-05 01:20:27',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'edit_ticket',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'update_ticket',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'view_ticket',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'view_any_ticket',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'close_ticket',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            6 => 
            array (
                'id' => 7,
                'name' => 'delete_ticket',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            7 => 
            array (
                'id' => 8,
                'name' => 'delete_any_ticket',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            8 => 
            array (
                'id' => 9,
                'name' => 'permanently_delete_ticket',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            9 => 
            array (
                'id' => 10,
                'name' => 'manage_tickets',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            10 => 
            array (
                'id' => 11,
                'name' => 'reassign_ticket',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            11 => 
            array (
                'id' => 12,
                'name' => 'update_any_ticket',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            12 => 
            array (
                'id' => 13,
                'name' => 'reply_ticket',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            13 => 
            array (
                'id' => 14,
                'name' => 'create_ticket_reply',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            14 => 
            array (
                'id' => 15,
                'name' => 'create_any_ticket_reply',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            15 => 
            array (
                'id' => 16,
                'name' => 'update_ticket_reply',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            16 => 
            array (
                'id' => 17,
                'name' => 'delete_ticket_reply',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            17 => 
            array (
                'id' => 18,
                'name' => 'delete_any_ticket_reply',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            18 => 
            array (
                'id' => 19,
                'name' => 'update_any_ticket_reply',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            19 => 
            array (
                'id' => 20,
                'name' => 'manage_categories',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            20 => 
            array (
                'id' => 21,
                'name' => 'create_category',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            21 => 
            array (
                'id' => 22,
                'name' => 'edit_category',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            22 => 
            array (
                'id' => 23,
                'name' => 'delete_category',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            23 => 
            array (
                'id' => 24,
                'name' => 'view_category',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            24 => 
            array (
                'id' => 25,
                'name' => 'view_any_category',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            25 => 
            array (
                'id' => 26,
                'name' => 'delete_any_category',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            26 => 
            array (
                'id' => 27,
                'name' => 'update_any_category',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            27 => 
            array (
                'id' => 28,
                'name' => 'create_user',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            28 => 
            array (
                'id' => 29,
                'name' => 'update_user',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            29 => 
            array (
                'id' => 30,
                'name' => 'delete_user',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:28',
                'updated_at' => '2024-10-05 01:20:28',
            ),
            30 => 
            array (
                'id' => 31,
                'name' => 'view_user',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:29',
                'updated_at' => '2024-10-05 01:20:29',
            ),
            31 => 
            array (
                'id' => 32,
                'name' => 'viewany_user',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:29',
                'updated_at' => '2024-10-05 01:20:29',
            ),
            32 => 
            array (
                'id' => 33,
                'name' => 'permanently_delete_user',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:29',
                'updated_at' => '2024-10-05 01:20:29',
            ),
            33 => 
            array (
                'id' => 34,
                'name' => 'manage_customers',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:29',
                'updated_at' => '2024-10-05 01:20:29',
            ),
            34 => 
            array (
                'id' => 35,
                'name' => 'create_customer',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:29',
                'updated_at' => '2024-10-05 01:20:29',
            ),
            35 => 
            array (
                'id' => 36,
                'name' => 'update_customer',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:29',
                'updated_at' => '2024-10-05 01:20:29',
            ),
            36 => 
            array (
                'id' => 37,
                'name' => 'manage_employees',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:29',
                'updated_at' => '2024-10-05 01:20:29',
            ),
            37 => 
            array (
                'id' => 38,
                'name' => 'create_employee',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:29',
                'updated_at' => '2024-10-05 01:20:29',
            ),
            38 => 
            array (
                'id' => 39,
                'name' => 'update_employee',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:29',
                'updated_at' => '2024-10-05 01:20:29',
            ),
            39 => 
            array (
                'id' => 40,
                'name' => 'assign_role',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:29',
                'updated_at' => '2024-10-05 01:20:29',
            ),
            40 => 
            array (
                'id' => 41,
                'name' => 'create_role',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:30',
                'updated_at' => '2024-10-05 01:20:30',
            ),
            41 => 
            array (
                'id' => 42,
                'name' => 'edit_role',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:30',
                'updated_at' => '2024-10-05 01:20:30',
            ),
            42 => 
            array (
                'id' => 43,
                'name' => 'delete_role',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:30',
                'updated_at' => '2024-10-05 01:20:30',
            ),
            43 => 
            array (
                'id' => 44,
                'name' => 'view_role',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:30',
                'updated_at' => '2024-10-05 01:20:30',
            ),
            44 => 
            array (
                'id' => 45,
                'name' => 'permanently_delete_role',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:30',
                'updated_at' => '2024-10-05 01:20:30',
            ),
            45 => 
            array (
                'id' => 46,
                'name' => 'assign_permissions',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:30',
                'updated_at' => '2024-10-05 01:20:30',
            ),
            46 => 
            array (
                'id' => 47,
                'name' => 'manage_acl',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:30',
                'updated_at' => '2024-10-05 01:20:30',
            ),
            47 => 
            array (
                'id' => 48,
                'name' => 'manage_articles',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:30',
                'updated_at' => '2024-10-05 01:20:30',
            ),
            48 => 
            array (
                'id' => 49,
                'name' => 'create_article',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:30',
                'updated_at' => '2024-10-05 01:20:30',
            ),
            49 => 
            array (
                'id' => 50,
                'name' => 'update_article',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:30',
                'updated_at' => '2024-10-05 01:20:30',
            ),
            50 => 
            array (
                'id' => 51,
                'name' => 'delete_article',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:31',
                'updated_at' => '2024-10-05 01:20:31',
            ),
            51 => 
            array (
                'id' => 52,
                'name' => 'permanently_delete_article',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:31',
                'updated_at' => '2024-10-05 01:20:31',
            ),
            52 => 
            array (
                'id' => 53,
                'name' => 'unpublish_article',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:31',
                'updated_at' => '2024-10-05 01:20:31',
            ),
            53 => 
            array (
                'id' => 54,
                'name' => 'publish_article',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:31',
                'updated_at' => '2024-10-05 01:20:31',
            ),
            54 => 
            array (
                'id' => 55,
                'name' => 'view_any_article',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:31',
                'updated_at' => '2024-10-05 01:20:31',
            ),
            55 => 
            array (
                'id' => 56,
                'name' => 'delete_any_article',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:31',
                'updated_at' => '2024-10-05 01:20:31',
            ),
            56 => 
            array (
                'id' => 57,
                'name' => 'update_any_article',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:31',
                'updated_at' => '2024-10-05 01:20:31',
            ),
            57 => 
            array (
                'id' => 58,
                'name' => 'create_saved_reply',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:31',
                'updated_at' => '2024-10-05 01:20:31',
            ),
            58 => 
            array (
                'id' => 59,
                'name' => 'view_saved_reply',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:31',
                'updated_at' => '2024-10-05 01:20:31',
            ),
            59 => 
            array (
                'id' => 60,
                'name' => 'edit_saved_reply',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:31',
                'updated_at' => '2024-10-05 01:20:31',
            ),
            60 => 
            array (
                'id' => 61,
                'name' => 'delete_saved_reply',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:31',
                'updated_at' => '2024-10-05 01:20:31',
            ),
            61 => 
            array (
                'id' => 62,
                'name' => 'upload_module',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:31',
                'updated_at' => '2024-10-05 01:20:31',
            ),
            62 => 
            array (
                'id' => 63,
                'name' => 'list_modules',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:31',
                'updated_at' => '2024-10-05 01:20:31',
            ),
            63 => 
            array (
                'id' => 64,
                'name' => 'manage_modules',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:31',
                'updated_at' => '2024-10-05 01:20:31',
            ),
            64 => 
            array (
                'id' => 65,
                'name' => 'statistics_view',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:31',
                'updated_at' => '2024-10-05 01:20:31',
            ),
            65 => 
            array (
                'id' => 66,
                'name' => 'statistics_view_any',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:32',
                'updated_at' => '2024-10-05 01:20:32',
            ),
            66 => 
            array (
                'id' => 67,
                'name' => 'add_reply_signature',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:32',
                'updated_at' => '2024-10-05 01:20:32',
            ),
            67 => 
            array (
                'id' => 68,
                'name' => 'view_error_logs',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:32',
                'updated_at' => '2024-10-05 01:20:32',
            ),
            68 => 
            array (
                'id' => 69,
                'name' => 'delete_error_log',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:32',
                'updated_at' => '2024-10-05 01:20:32',
            ),
            69 => 
            array (
                'id' => 70,
                'name' => 'update_settings',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:32',
                'updated_at' => '2024-10-05 01:20:32',
            ),
            70 => 
            array (
                'id' => 71,
                'name' => 'view_settings',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:32',
                'updated_at' => '2024-10-05 01:20:32',
            ),
            71 => 
            array (
                'id' => 72,
                'name' => 'update_application',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:32',
                'updated_at' => '2024-10-05 01:20:32',
            ),
            72 => 
            array (
                'id' => 73,
                'name' => 'view_customer_purchase',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:32',
                'updated_at' => '2024-10-05 01:20:32',
            ),
            73 => 
            array (
                'id' => 74,
                'name' => 'update_customer_purchase',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:32',
                'updated_at' => '2024-10-05 01:20:32',
            ),
            74 => 
            array (
                'id' => 75,
                'name' => 'admin_only',
                'guard_name' => 'web',
                'created_at' => '2024-10-05 01:20:32',
                'updated_at' => '2024-10-05 01:20:32',
            ),
        ));
        
        
    }
}