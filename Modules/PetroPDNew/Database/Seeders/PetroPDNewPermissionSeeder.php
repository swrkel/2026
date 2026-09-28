<?php

namespace Modules\PetroPDNew\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PetroPDNewPermissionSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('permissions')) return;

        $now = now();
        $permissions = require dirname(__DIR__, 2) . '/Permissions/permissions.php';

        foreach (array_keys($permissions) as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'web'],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }

        if (class_exists('Spatie\\Permission\\PermissionRegistrar')) {
            app('Spatie\\Permission\\PermissionRegistrar')->forgetCachedPermissions();
        }
    }
}
