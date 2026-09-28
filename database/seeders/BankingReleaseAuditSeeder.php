<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankingReleaseAuditSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['sidebar_visibility','route_coverage','permission_coverage','tester_access','toolbar_consistency','release_checklist'] as $check) {
            DB::table('banking_ui_release_checks')->updateOrInsert(
                ['module_code' => 'BKG-UI-005', 'check_key' => $check],
                ['status' => 'pending', 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
