<?php

namespace Modules\StockTakingNew\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockTakingNewPermissionSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        foreach ([
            'stock_taking_new.access',
            'stock_taking_new.dashboard.view',
            'stock_taking_new.sessions.view',
            'stock_taking_new.sessions.create',
            'stock_taking_new.sessions.edit',
            'stock_taking_new.sessions.prepare',
            'stock_taking_new.sessions.start',
            'stock_taking_new.counts.enter',
            'stock_taking_new.counts.import',
            'stock_taking_new.counts.submit',
            'stock_taking_new.recounts.manage',
            'stock_taking_new.approvals.view',
            'stock_taking_new.approvals.approve',
            'stock_taking_new.approvals.reject',
            'stock_taking_new.reconciliation.post',
            'stock_taking_new.templates.manage',
            'stock_taking_new.schedules.manage',
            'stock_taking_new.reports.view',
            'stock_taking_new.documents.print',
            'stock_taking_new.documents.share',
            'stock_taking_new.settings.manage',
        ] as $permission) {
            if (! DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->exists()) {
                DB::table('permissions')->insert([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (app()->bound(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
