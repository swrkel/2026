<?php

namespace Modules\BankingUI\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankingPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = config('bankingui_permissions.permissions', []);

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
