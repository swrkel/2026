<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (\Schema::hasTable('hm_module_permissions')) {
            DB::table('hm_module_permissions')->updateOrInsert(
                ['module' => 'HotelManagement', 'permission_key' => 'hotel.system_check.view'],
                ['label' => 'Hotel System Check View', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        if (\Schema::hasTable('hm_menu_registry')) {
            DB::table('hm_menu_registry')->updateOrInsert(
                ['business_id' => null, 'business_location_id' => null, 'menu_key' => 'system_check'],
                [
                    'module' => 'HotelManagement',
                    'label' => 'System Check',
                    'route_name' => 'hotel-management.system-check.index',
                    'permission_key' => 'hotel.settings',
                    'sort_order' => 180,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (\Schema::hasTable('hm_menu_registry')) {
            DB::table('hm_menu_registry')->where('module', 'HotelManagement')->where('menu_key', 'system_check')->delete();
        }
        if (\Schema::hasTable('hm_module_permissions')) {
            DB::table('hm_module_permissions')->where('module', 'HotelManagement')->where('permission_key', 'hotel.system_check.view')->delete();
        }
    }
};
