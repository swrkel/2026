<?php

namespace Modules\MembershipNew\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MembershipNewPermissionSeeder_MEMNEW_002 extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'membership_new.linked_businesses.view',
            'membership_new.linked_businesses.create',
            'membership_new.linked_businesses.edit',
            'membership_new.linked_businesses.delete',
            'membership_new.point_rules.view',
            'membership_new.point_rules.create',
            'membership_new.point_rules.edit',
            'membership_new.point_rules.delete',
            'membership_new.points.view',
            'membership_new.points.earn',
            'membership_new.points.redeem',
            'membership_new.shares.view',
            'membership_new.shares.create',
            'membership_new.shares.edit',
            'membership_new.shares.delete',
            'membership_new.dividends.view',
            'membership_new.dividends.create',
            'membership_new.dividends.edit',
            'membership_new.dividends.delete',
            'membership_new.cards.print',
            'membership_new.cards.issue',
            'membership_new.cards.scan',
            'membership_new.customer_sync.run',
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
