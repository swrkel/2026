<?php

namespace Modules\MembershipNew\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MembershipNewPermissionSeeder_MEMNEW_003 extends Seeder
{
    public function run(): void
    {
        foreach ([
            'membership_new.members.view',
            'membership_new.members.create',
            'membership_new.members.edit',
            'membership_new.members.delete',
            'membership_new.reports.view',
        ] as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
