<?php

namespace Modules\FinanceReports\Database\Seeders;

use Illuminate\Database\Seeder;

class FinanceReportsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'finance_reports.view',
            'finance_reports.print',
            'finance_reports.export',
            'finance_reports.executive_dashboard',
            'finance_reports.audit_reports',
            'finance_reports.forecast_reports',
            'finance_reports.fixed_asset_reports',
            'finance_reports.consolidated_reports',
        ];

        foreach ($permissions as $permission) {
            if (class_exists(\Spatie\Permission\Models\Permission::class)) {
                \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
            }
        }
    }
}
