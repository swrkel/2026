<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('roles')->delete();
        
        \DB::table('roles')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Admin#1',
                'guard_name' => 'web',
                'business_id' => 1,
                'is_default' => 1,
                'is_service_staff' => 0,
                'is_superadmin_default' => 0,
                'created_at' => '2019-12-30 12:22:03',
                'updated_at' => '2019-12-30 12:22:03',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Admin#2',
                'guard_name' => 'web',
                'business_id' => 2,
                'is_default' => 1,
                'is_service_staff' => 0,
                'is_superadmin_default' => 0,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Cashier#2',
                'guard_name' => 'web',
                'business_id' => 2,
                'is_default' => 0,
                'is_service_staff' => 0,
                'is_superadmin_default' => 0,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Pump Operator#2',
                'guard_name' => 'web',
                'business_id' => 2,
                'is_default' => 0,
                'is_service_staff' => 0,
                'is_superadmin_default' => 0,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'Admin#3',
                'guard_name' => 'web',
                'business_id' => 3,
                'is_default' => 1,
                'is_service_staff' => 0,
                'is_superadmin_default' => 0,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'Cashier#3',
                'guard_name' => 'web',
                'business_id' => 3,
                'is_default' => 0,
                'is_service_staff' => 0,
                'is_superadmin_default' => 0,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
            ),
            6 => 
            array (
                'id' => 7,
                'name' => 'Pump Operator#3',
                'guard_name' => 'web',
                'business_id' => 3,
                'is_default' => 0,
                'is_service_staff' => 0,
                'is_superadmin_default' => 0,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
            ),
            7 => 
            array (
                'id' => 8,
                'name' => 'Admin#4',
                'guard_name' => 'web',
                'business_id' => 4,
                'is_default' => 1,
                'is_service_staff' => 0,
                'is_superadmin_default' => 0,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            8 => 
            array (
                'id' => 9,
                'name' => 'Cashier#4',
                'guard_name' => 'web',
                'business_id' => 4,
                'is_default' => 0,
                'is_service_staff' => 0,
                'is_superadmin_default' => 0,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            9 => 
            array (
                'id' => 10,
                'name' => 'Pump Operator#4',
                'guard_name' => 'web',
                'business_id' => 4,
                'is_default' => 0,
                'is_service_staff' => 0,
                'is_superadmin_default' => 0,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            10 => 
            array (
                'id' => 11,
                'name' => 'Bandara#4',
                'guard_name' => 'web',
                'business_id' => 4,
                'is_default' => 0,
                'is_service_staff' => 0,
                'is_superadmin_default' => 0,
                'created_at' => '2024-02-27 12:45:30',
                'updated_at' => '2024-09-12 11:54:59',
            ),
            11 => 
            array (
                'id' => 12,
                'name' => 'Hansi#4',
                'guard_name' => 'web',
                'business_id' => 4,
                'is_default' => 0,
                'is_service_staff' => 0,
                'is_superadmin_default' => 0,
                'created_at' => '2024-02-27 12:47:49',
                'updated_at' => '2024-02-27 12:47:49',
            ),
            12 => 
            array (
                'id' => 13,
                'name' => 'Indika#4',
                'guard_name' => 'web',
                'business_id' => 4,
                'is_default' => 0,
                'is_service_staff' => 0,
                'is_superadmin_default' => 0,
                'created_at' => '2024-02-27 12:51:51',
                'updated_at' => '2024-02-27 12:51:51',
            ),
        ));
        
        
    }
}