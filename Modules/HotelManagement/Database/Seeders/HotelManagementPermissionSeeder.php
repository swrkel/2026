<?php

namespace Modules\HotelManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HotelManagementPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = include __DIR__.'/../../Permissions/permissions.php';
        $now = now();

        if (Schema::hasTable('permissions')) {
            foreach ($permissions as $permission) {
                $payload = [
                    'name' => $permission,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $exists = DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->exists();
                if ($exists) {
                    DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->update(['updated_at' => $now]);
                } else {
                    DB::table('permissions')->insert($payload);
                }
            }
        }

        if (Schema::hasTable('hm_module_permissions')) {
            foreach ($permissions as $permission) {
                DB::table('hm_module_permissions')->updateOrInsert(
                    ['permission_key' => $permission],
                    [
                        'module' => 'HotelManagement',
                        'label' => Str::headline(str_replace(['hotel.', '_'], ['', ' '], $permission)),
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }
}
