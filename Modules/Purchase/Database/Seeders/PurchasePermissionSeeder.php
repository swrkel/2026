<?php

namespace Modules\Purchase\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Utils\PurchasePermissionUtil;

class PurchasePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PurchasePermissionUtil::all() as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }
    }
}
