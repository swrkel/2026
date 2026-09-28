<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankingReportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'banking.reports.view',
            'banking.reports.export.csv',
            'banking.reports.export.excel',
            'banking.reports.export.pdf',
            'banking.reports.print',
            'banking.reports.audit.view',
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission],
                ['guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
