<?php

namespace Modules\LeadsNew\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadsNewPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'leads_new.view','leads_new.create','leads_new.edit','leads_new.delete',
            'leads_new.convert','leads_new.dashboard','leads_new.reports','leads_new.settings',
            'leads_new.export','leads_new.import','leads_new.bulk_actions','leads_new.documents',
            'leads_new.followups','leads_new.opportunities','leads_new.campaigns','leads_new.audit',
        ];

        foreach ($permissions as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
