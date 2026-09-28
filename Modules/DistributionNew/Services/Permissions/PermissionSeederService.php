<?php

namespace Modules\DistributionNew\Services\Permissions;

use Illuminate\Support\Facades\DB;

class PermissionSeederService
{
    public function permissions(): array
    {
        return [
            'distribution_new.view_dashboard',
            'distribution_new.manage_orders',
            'distribution_new.create_invoice_from_order',
            'distribution_new.manage_loading',
            'distribution_new.manage_unloading',
            'distribution_new.manage_vehicles',
            'distribution_new.manage_routes',
            'distribution_new.manage_collections',
            'distribution_new.manage_settlements',
            'distribution_new.manage_returns',
            'distribution_new.view_reports',
            'distribution_new.manage_notification_preferences',
            'distribution_new.superadmin_limits',
        ];
    }

    public function seed(): int
    {
        $count = 0;
        foreach ($this->permissions() as $permission) {
            $exists = DB::table('permissions')->where('name', $permission)->exists();
            if (!$exists) {
                DB::table('permissions')->insert(['name' => $permission, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
                $count++;
            }
        }
        return $count;
    }
}
