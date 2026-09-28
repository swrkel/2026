<?php

namespace Modules\PetroDirectNew\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PetroDirectNewPermissionSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach ((array) require __DIR__ . '/../../Permissions/permissions.php' as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name'] ?? 'web'],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
