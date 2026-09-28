<?php

namespace Modules\BeautySaloons\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BS010WalletPrepaidPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = require dirname(__DIR__, 2) . '/Permissions/BS010_WalletPrepaidPermissions.php';
        foreach ($permissions as $permission) {
            if (class_exists('Spatie\\Permission\\Models\\Permission')) {
                \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
            } elseif (DB::getSchemaBuilder()->hasTable('permissions')) {
                DB::table('permissions')->updateOrInsert(['name' => $permission], ['guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}
