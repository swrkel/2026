<?php

namespace Modules\MembershipNew\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\MembershipNew\app\Permissions\MembershipNewPermissionRegistry;

class MembershipNewPermissionSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach (MembershipNewPermissionRegistry::flat() as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
