<?php

namespace Modules\BankingUI\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankingUIPermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('banking-ui.permissions', []) as $permission) {
            DB::table('banking_ui_permissions')->updateOrInsert(
                ['name' => $permission],
                [
                    'label' => ucwords(str_replace(['banking.', '_', '.'], ['', ' ', ' - '], $permission)),
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
